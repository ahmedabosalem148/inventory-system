import { Input } from '../../atoms';

/**
 * FormField - Wrapper component combining Input with form integration
 * Use with react-hook-form: <FormField {...register('fieldName')} error={errors.fieldName?.message} />
 */
const FormField = ({ 
  label,
  error,
  helperText,
  required,
  ...inputProps 
}) => {
  return (
    <Input
      label={label}
      error={error}
      helperText={helperText}
      required={required}
      {...inputProps}
    />
  );
};

export default FormField;
