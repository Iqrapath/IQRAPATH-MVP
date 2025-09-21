import { HTMLAttributes } from 'react';

interface AppLogoIconProps extends HTMLAttributes<HTMLImageElement> {
  className?: string;
}

export default function AppLogoIcon({ className, ...props }: AppLogoIconProps) {
  return (
    <img 
      src="/assets/images/logo/IqraQuest-logo.png" 
      alt="IqraQuest Logo"
      className={className}
      {...props}
    />
  );
}
