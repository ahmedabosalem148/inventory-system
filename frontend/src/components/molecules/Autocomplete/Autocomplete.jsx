import React, { useState, useEffect, useRef } from 'react';
import { Search, X, ChevronDown, Loader2 } from 'lucide-react';
import { Input, Spinner } from '../../atoms';

/**
 * Autocomplete Component
 * 
 * A reusable autocomplete/typeahead component with search functionality
 * 
 * @param {Object} props
 * @param {string} props.label - Label for the input field
 * @param {string} props.placeholder - Placeholder text
 * @param {Array} props.options - Array of options to display
 * @param {Function} props.onSearch - Callback when user types (for API calls)
 * @param {Function} props.onSelect - Callback when user selects an option
 * @param {Function} props.renderOption - Custom render function for options
 * @param {Function} props.getOptionLabel - Function to extract label from option
 * @param {Function} props.getOptionValue - Function to extract value from option
 * @param {*} props.value - Currently selected value
 * @param {boolean} props.loading - Loading state
 * @param {boolean} props.required - Required field
 * @param {boolean} props.disabled - Disabled state
 * @param {string} props.error - Error message
 * @param {string} props.emptyMessage - Message when no results
 * @param {number} props.minChars - Minimum characters before search (default: 2)
 */
const Autocomplete = ({
  label,
  placeholder = 'ابحث...',
  options = [],
  onSearch,
  onSelect,
  renderOption,
  getOptionLabel = (option) => option.label || option.name || option,
  getOptionValue = (option) => option.value || option.id || option,
  value,
  loading = false,
  required = false,
  disabled = false,
  error,
  emptyMessage = 'لا توجد نتائج',
  minChars = 2
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedOption, setSelectedOption] = useState(null);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);
  const wrapperRef = useRef(null);
  const inputRef = useRef(null);
  const listRef = useRef(null);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Search when term changes
  useEffect(() => {
    if (searchTerm.length >= minChars && onSearch) {
      const timer = setTimeout(() => {
        onSearch(searchTerm);
      }, 300); // Debounce

      return () => clearTimeout(timer);
    }
  }, [searchTerm, minChars, onSearch]);

    // Update selected option when value changes (but keep user input intact while typing)
    useEffect(() => {
      if (value) {
        const option = options.find(opt => getOptionValue(opt) === value);
        if (option && !isOpen) {
          // Only update search term if dropdown is closed (user not actively typing)
          setSelectedOption(option);
          setSearchTerm(getOptionLabel(option));
        } else if (option) {
          setSelectedOption(option);
        }
      } else if (!isOpen) {
        // Only clear if dropdown is closed
        setSelectedOption(null);
        setSearchTerm('');
      }
    }, [value, options, getOptionLabel, getOptionValue, isOpen]);

  // Scroll highlighted item into view
  useEffect(() => {
    if (highlightedIndex >= 0 && listRef.current) {
      const highlightedElement = listRef.current.children[highlightedIndex];
      if (highlightedElement) {
        highlightedElement.scrollIntoView({
          block: 'nearest',
          behavior: 'smooth'
        });
      }
    }
  }, [highlightedIndex]);

  const handleInputChange = (e) => {
    const term = e.target.value;
    setSearchTerm(term);
    setIsOpen(true);
    setHighlightedIndex(-1);
    
      // Only clear selection if input is explicitly cleared (not just while typing)
      if (!term && selectedOption) {
        setSelectedOption(null);
        onSelect?.(null);
      }
  };

  const handleSelect = (option) => {
    setSelectedOption(option);
    setSearchTerm(getOptionLabel(option));
    setIsOpen(false);
    setHighlightedIndex(-1);
    onSelect?.(option);
  };

  const handleClear = () => {
    setSearchTerm('');
    setSelectedOption(null);
    setIsOpen(false);
    setHighlightedIndex(-1);
    onSelect?.(null);
    inputRef.current?.focus();
  };

  const handleKeyDown = (e) => {
    if (!isOpen && (e.key === 'ArrowDown' || e.key === 'Enter')) {
      setIsOpen(true);
      return;
    }

    if (!isOpen) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setHighlightedIndex(prev =>
          prev < options.length - 1 ? prev + 1 : prev
        );
        break;

      case 'ArrowUp':
        e.preventDefault();
        setHighlightedIndex(prev => (prev > 0 ? prev - 1 : -1));
        break;

      case 'Enter':
        e.preventDefault();
        if (highlightedIndex >= 0 && options[highlightedIndex]) {
          handleSelect(options[highlightedIndex]);
        }
        break;

      case 'Escape':
        setIsOpen(false);
        setHighlightedIndex(-1);
        break;

      default:
        break;
    }
  };

  const showOptions = isOpen && searchTerm.length >= minChars;
  const hasOptions = options.length > 0;

  return (
    <div ref={wrapperRef} className="relative w-full">
      {/* Label */}
      {label && (
        <label className="block text-sm font-medium text-gray-700 mb-2">
          {label}
          {required && <span className="text-red-500 mr-1">*</span>}
        </label>
      )}

      {/* Input Field */}
      <div className="relative">
        <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
          {loading ? (
            <Loader2 className="w-5 h-5 animate-spin" />
          ) : (
            <Search className="w-5 h-5" />
          )}
        </div>

        <input
          ref={inputRef}
          type="text"
          value={searchTerm}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          onFocus={() => searchTerm.length >= minChars && setIsOpen(true)}
          placeholder={placeholder}
          disabled={disabled}
          className={`
            w-full px-4 py-2 pr-10 pl-10
            border rounded-lg
            transition-all duration-200
            focus:outline-none focus:ring-2
            ${error
              ? 'border-red-300 focus:ring-red-500'
              : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500'
            }
            ${disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}
          `}
        />

        {/* Clear Button */}
        {searchTerm && !disabled && (
          <button
            type="button"
            onClick={handleClear}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        )}

        {/* Dropdown Indicator */}
        {!loading && !searchTerm && (
          <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            <ChevronDown className="w-5 h-5" />
          </div>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <p className="mt-1 text-sm text-red-600">{error}</p>
      )}

      {/* Dropdown Options */}
      {showOptions && (
        <div className="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
          {loading && (
            <div className="flex items-center justify-center py-8">
              <Spinner size="md" />
              <span className="mr-2 text-gray-600">جاري البحث...</span>
            </div>
          )}

          {!loading && !hasOptions && (
            <div className="px-4 py-8 text-center text-gray-500">
              {emptyMessage}
            </div>
          )}

          {!loading && hasOptions && (
            <ul ref={listRef} className="py-1">
              {options.map((option, index) => {
                const isHighlighted = index === highlightedIndex;
                const isSelected = selectedOption && 
                  getOptionValue(selectedOption) === getOptionValue(option);

                return (
                  <li key={getOptionValue(option)}>
                    <button
                      type="button"
                      onClick={() => handleSelect(option)}
                      className={`
                        w-full px-4 py-2 text-right
                        transition-colors duration-150
                        ${isHighlighted ? 'bg-primary-50' : ''}
                        ${isSelected ? 'bg-primary-100' : ''}
                        hover:bg-primary-50
                        focus:bg-primary-50 focus:outline-none
                      `}
                    >
                      {renderOption ? (
                        renderOption(option, isSelected, isHighlighted)
                      ) : (
                        <div className="text-gray-900">
                          {getOptionLabel(option)}
                        </div>
                      )}
                    </button>
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      )}

      {/* Helper Text */}
      {!error && searchTerm.length > 0 && searchTerm.length < minChars && (
        <p className="mt-1 text-sm text-gray-500">
          اكتب {minChars - searchTerm.length} حرف على الأقل للبحث
        </p>
      )}
    </div>
  );
};

export default Autocomplete;
