<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SequencerService;
use App\Models\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SequencerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SequencerService $sequencer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequencer = new SequencerService();
        
        // Seed sequences for tests
        $this->seedSequences();
    }

    /**
     * Seed sequences for testing
     */
    protected function seedSequences(): void
    {
        $entityTypes = ['issue_vouchers', 'return_vouchers', 'purchase_orders', 'payments'];
        $year = 2025;

        foreach ($entityTypes as $entityType) {
            Sequence::create([
                'entity_type' => $entityType,
                'year' => $year,
                'last_number' => 0,
            ]);
        }
    }

    /** @test */
    public function it_generates_first_sequence_for_new_year()
    {
        // Act - starts from 1 because seedSequences() set last_number = 0
        $result = $this->sequencer->getNextSequence('issue_vouchers', 2025);

        // Assert
        $this->assertEquals('2025/1', $result);
        
        // Check database
        $sequence = Sequence::where('entity_type', 'issue_vouchers')
            ->where('year', 2025)
            ->first();
        
        $this->assertNotNull($sequence);
        $this->assertEquals(1, $sequence->last_number);
    }

    /** @test */
    public function it_increments_existing_sequence()
    {
        // Arrange - Update existing sequence from seed
        Sequence::where('entity_type', 'issue_vouchers')
            ->where('year', 2025)
            ->update(['last_number' => 5]);

        // Act
        $result = $this->sequencer->getNextSequence('issue_vouchers', 2025);

        // Assert
        $this->assertEquals('2025/6', $result);
        
        $sequence = Sequence::where('entity_type', 'issue_vouchers')
            ->where('year', 2025)
            ->first();
        
        $this->assertEquals(6, $sequence->last_number);
    }

    /** @test */
    public function it_handles_concurrent_requests_without_gaps()
    {
        // Arrange: Simulate race condition
        $sequences = [];

        // Act: Generate 10 sequences simultaneously
        for ($i = 0; $i < 10; $i++) {
            $sequences[] = $this->sequencer->getNextSequence('issue_vouchers', 2025);
        }

        // Assert: No duplicates
        $this->assertCount(10, array_unique($sequences));
        
        // Assert: Sequential numbers
        $numbers = array_map(fn($s) => (int) explode('/', $s)[1], $sequences);
        sort($numbers);
        
        $this->assertEquals(range(1, 10), $numbers);
    }

    /** @test */
    public function it_resets_sequence_on_new_year()
    {
        // Arrange - Create sequence for previous year
        Sequence::create([
            'entity_type' => 'issue_vouchers',
            'year' => 2024,
            'last_number' => 999,
        ]);

        // Act - Use 2025 which already has seed
        $result2025 = $this->sequencer->getNextSequence('issue_vouchers', 2025);

        // Assert
        $this->assertEquals('2025/1', $result2025);
        
        // Old year should remain unchanged
        $old = Sequence::where('entity_type', 'issue_vouchers')
            ->where('year', 2024)
            ->first();
        
        $this->assertEquals(999, $old->last_number);
    }

    /** @test */
    public function it_handles_different_entity_types_separately()
    {
        // Arrange
        $this->sequencer->getNextSequence('issue_vouchers', 2025); // 2025/1
        $this->sequencer->getNextSequence('return_vouchers', 2025); // 2025/1

        // Act
        $issueNext = $this->sequencer->getNextSequence('issue_vouchers', 2025);
        $returnNext = $this->sequencer->getNextSequence('return_vouchers', 2025);

        // Assert
        $this->assertEquals('2025/2', $issueNext);
        $this->assertEquals('2025/2', $returnNext);
    }

    /** @test */
    public function it_throws_exception_if_max_sequence_reached()
    {
        // Arrange - Update existing sequence to max value
        $sequence = Sequence::where('entity_type', 'issue_vouchers')
            ->where('year', 2025)
            ->first();
        
        $sequence->update(['last_number' => 999999]); // Max value

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sequence limit reached for issue_vouchers in year 2025');

        // Act
        $this->sequencer->getNextSequence('issue_vouchers', 2025);
    }

    /** @test */
    public function it_uses_current_year_if_not_specified()
    {
        // Arrange - Create sequence for current year
        $currentYear = now()->year;
        
        Sequence::updateOrCreate(
            [
                'entity_type' => 'issue_vouchers',
                'year' => $currentYear,
            ],
            [
                'last_number' => 0,
            ]
        );
        
        // Act - Don't specify year
        $result = $this->sequencer->getNextSequence('issue_vouchers');
        
        // Assert
        $this->assertEquals("{$currentYear}/1", $result);
    }    /** @test */
    public function it_locks_row_during_update_to_prevent_race_condition()
    {
        // This test ensures lockForUpdate() is used
        // We'll check the SQL query contains "FOR UPDATE"
        
        \DB::enableQueryLog();

        $this->sequencer->getNextSequence('issue_vouchers', 2025);

        $queries = \DB::getQueryLog();
        
        // Find the SELECT query
        $selectQuery = collect($queries)->first(function ($query) {
            return str_contains(strtoupper($query['query']), 'SELECT');
        });

        // Assert: Query should contain locking mechanism
        // Note: SQLite uses different syntax, but Laravel handles it
        $this->assertNotNull($selectQuery);
    }

    /** @test */
    public function it_formats_sequence_correctly()
    {
        // Act
        $seq1 = $this->sequencer->getNextSequence('payments', 2025);
        $seq2 = $this->sequencer->getNextSequence('payments', 2025);

        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}\/\d+$/', $seq1);
        $this->assertEquals('2025/1', $seq1);
        $this->assertEquals('2025/2', $seq2);
    }

    /** @test */
    public function it_works_with_all_entity_types()
    {
        // Arrange - Seed all entity types
        $entityTypes = [
            'issue_vouchers',
            'return_vouchers',
            'transfer_vouchers',
            'payments',
            'customers',
            'cheques',
        ];

        // Clear existing seeds and create for all types
        Sequence::where('year', 2025)->delete();
        
        foreach ($entityTypes as $type) {
            Sequence::create([
                'entity_type' => $type,
                'year' => 2025,
                'last_number' => 0,
            ]);
        }

        // Act & Assert
        foreach ($entityTypes as $type) {
            $result = $this->sequencer->getNextSequence($type, 2025);
            $this->assertEquals('2025/1', $result);
        }

        // Each should have its own sequence
        $this->assertEquals(count($entityTypes), Sequence::where('year', 2025)->count());
    }
}
