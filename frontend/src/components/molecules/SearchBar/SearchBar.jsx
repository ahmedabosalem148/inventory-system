import { useState } from 'react';
import { Search, X } from 'lucide-react';
import { Input, Button } from '../../atoms';

/**
 * SearchBar - Search input with clear button
 */
const SearchBar = ({ 
  placeholder = 'بحث...', 
  onSearch,
  className = '',
  fullWidth = true,
  size = 'md'
}) => {
  const [value, setValue] = useState('');

  const handleSearch = (e) => {
    e.preventDefault();
    onSearch?.(value);
  };

  const handleClear = () => {
    setValue('');
    onSearch?.('');
  };

  return (
    <form onSubmit={handleSearch} className={`flex gap-2 ${className}`}>
      <Input
        type="text"
        value={value}
        onChange={(e) => setValue(e.target.value)}
        placeholder={placeholder}
        leftIcon={<Search className="w-5 h-5" />}
        rightIcon={
          value && (
            <button
              type="button"
              onClick={handleClear}
              className="focus:outline-none hover:text-gray-600 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          )
        }
        fullWidth={fullWidth}
        className="flex-1"
      />
      <Button type="submit" variant="primary" size={size}>
        بحث
      </Button>
    </form>
  );
};

export default SearchBar;
