import { clsx } from 'clsx';

const Card = ({
  children,
  title,
  subtitle,
  actions,
  className,
  padding = 'default',
  hover = false,
  ...props
}) => {
  const paddingClasses = {
    none: '',
    sm: 'p-4',
    default: 'p-6',
    lg: 'p-8',
  };

  const cardClasses = clsx(
    'bg-white rounded-lg shadow-md transition-all duration-200',
    hover && 'hover:shadow-lg hover:-translate-y-0.5 cursor-pointer',
    paddingClasses[padding],
    className
  );

  return (
    <div className={cardClasses} {...props}>
      {(title || subtitle || actions) && (
        <div className="flex items-start justify-between mb-4 pb-4 border-b border-gray-200">
          <div className="flex-1">
            {title && (
              <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
            )}
            {subtitle && (
              <p className="text-sm text-gray-500 mt-1">{subtitle}</p>
            )}
          </div>
          {actions && (
            <div className="flex items-center gap-2 mr-4">
              {actions}
            </div>
          )}
        </div>
      )}
      {children}
    </div>
  );
};

export default Card;
