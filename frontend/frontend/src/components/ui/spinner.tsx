import * as React from 'react';
import { Loader2 } from 'lucide-react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const spinnerVariants = cva('animate-spin', {
  variants: {
    size: {
      sm: 'h-4 w-4',
      md: 'h-6 w-6',
      lg: 'h-8 w-8',
      xl: 'h-12 w-12',
    },
    color: {
      primary: 'text-blue-600',
      secondary: 'text-gray-600',
      success: 'text-green-600',
      danger: 'text-red-600',
      warning: 'text-yellow-600',
      white: 'text-white',
    },
  },
  defaultVariants: {
    size: 'md',
    color: 'primary',
  },
});

export interface SpinnerProps
  extends Omit<React.HTMLAttributes<HTMLDivElement>, 'color'>,
    VariantProps<typeof spinnerVariants> {
  label?: string;
}

function Spinner({ className, size, color, label, ...props }: SpinnerProps) {
  return (
    <div
      className={cn('flex flex-col items-center justify-center gap-2', className)}
      {...props}
    >
      <Loader2 className={cn(spinnerVariants({ size, color: color as any }))} />
      {label && (
        <p className="text-sm text-gray-600 animate-pulse">{label}</p>
      )}
    </div>
  );
}

// Full page spinner overlay
interface SpinnerOverlayProps {
  label?: string;
}

function SpinnerOverlay({ label = 'جاري التحميل...' }: SpinnerOverlayProps) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div className="rounded-lg bg-white p-6 shadow-xl">
        <Spinner size="xl" label={label} />
      </div>
    </div>
  );
}

export { Spinner, SpinnerOverlay, spinnerVariants };
