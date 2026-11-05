import React from 'react';
import { Loader2 } from 'lucide-react';

/**
 * Atomic Button component
 * @param {('primary'|'secondary'|'danger'|'ghost'|'accent')} variant
 * @param {('sm'|'md'|'lg')} size
 * @param {boolean} isLoading
 * @param {React.ReactNode} icon - Left icon optional
 * @param {React.ReactNode} children
 */
export default function Button({
  children,
  variant = 'primary',
  size = 'md',
  isLoading = false,
  disabled = false,
  icon: Icon,
  className = '',
  type = 'button',
  onClick,
  ...props
}) {
  const baseStyles = 'inline-flex items-center justify-center font-semibold transition-all duration-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#050811] disabled:opacity-50 disabled:cursor-not-allowed select-none';

  const sizeStyles = {
    sm: 'px-3 py-1.5 text-xs gap-1.5',
    md: 'px-4 py-2 text-xs sm:text-sm gap-2',
    lg: 'px-5 py-2.5 text-sm sm:text-base gap-2.5',
  };

  const variantStyles = {
    primary: 'bg-gradient-to-r from-brand-cyan to-brand-teal text-slate-950 hover:opacity-90 shadow-glow-cyan focus:ring-brand-cyan font-bold',
    secondary: 'border border-slate-700/80 bg-surface-1 text-slate-200 hover:bg-surface-elevated hover:text-white hover:border-slate-600 focus:ring-slate-500',
    danger: 'border border-red-500/40 bg-red-500/15 text-red-400 hover:bg-red-500/25 hover:text-red-300 focus:ring-red-500',
    ghost: 'text-slate-400 hover:text-white hover:bg-surface-elevated focus:ring-slate-600',
    accent: 'border border-sky-500/30 bg-sky-500/15 text-sky-300 hover:bg-sky-500/25 hover:text-white focus:ring-sky-400',
  };

  return (
    <button
      type={type}
      disabled={disabled || isLoading}
      onClick={onClick}
      className={`${baseStyles} ${sizeStyles[size] || sizeStyles.md} ${variantStyles[variant] || variantStyles.primary} ${className}`}
      {...props}
    >
      {isLoading ? (
        <Loader2 className="h-4 w-4 animate-spin text-current" />
      ) : Icon ? (
        <Icon className="h-4 w-4" />
      ) : null}
      {children}
    </button>
  );
}
