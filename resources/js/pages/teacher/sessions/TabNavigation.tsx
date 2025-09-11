import { cn } from '@/lib/utils';

interface Tab {
    id: string;
    label: string;
    count?: number;
}

interface TabNavigationProps {
    tabs: Tab[];
    activeTab: string;
    onTabChange: (tabId: string) => void;
    className?: string;
}

export function TabNavigation({ tabs, activeTab, onTabChange, className }: TabNavigationProps) {
    return (
        <div className={cn(
            "bg-white border border-gray-200 rounded-lg p-1 space-x-0 w-auto",
            className
        )}>
            {tabs.map((tab) => (
                <button
                    key={tab.id}
                    onClick={() => onTabChange(tab.id)}
                    className={cn(
                        "px-6 py-3 text-sm font-medium transition-colors duration-200 relative text-start",
                        activeTab === tab.id
                            ? "text-[#2C7870]"
                            : "text-gray-500 hover:text-gray-700"
                    )}
                >
                    {tab.label}
                    {tab.count !== undefined && (
                        <span className="ml-1">({tab.count})</span>
                    )}
                    
                    {/* Active tab underline */}
                    {activeTab === tab.id && (
                        <div className="absolute bottom-1 left-5 w-10 h-0.5 bg-[#2C7870] rounded-full" />
                    )}
                </button>
            ))}
        </div>
    );
}
