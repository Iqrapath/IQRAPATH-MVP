import React, { useState, useEffect } from 'react';
import { Bell, X, Eye, EyeOff, CheckCircle, AlertCircle, Info, AlertTriangle } from 'lucide-react';
import { router } from '@inertiajs/react';

interface AdminNotification {
    id: string;
    type: string;
    data: {
        title: string;
        message: string;
        action_text?: string;
        action_url?: string;
        new_user_id?: string;
        new_user_name?: string;
        new_user_email?: string;
        new_user_phone?: string;
        registration_time?: string;
    };
    level: 'info' | 'success' | 'warning' | 'error';
    read_at: string | null;
    created_at: string;
    notifiable_id: string;
}

interface AdminNotificationsProps {
    notifications?: AdminNotification[];
    unreadCount?: number;
}

const AdminNotifications: React.FC<AdminNotificationsProps> = ({ 
    notifications: propNotifications, 
    unreadCount: propUnreadCount 
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [localNotifications, setLocalNotifications] = useState<AdminNotification[]>([]);
    const [unreadCount, setUnreadCount] = useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(false);

    // Use props if provided, otherwise fetch data
    const shouldFetchData = !propNotifications || !propUnreadCount;

    // Initialize with props if provided
    useEffect(() => {
        if (propNotifications && propUnreadCount !== undefined) {
            setLocalNotifications(propNotifications);
            setUnreadCount(propUnreadCount);
        }
    }, [propNotifications, propUnreadCount]);

    // Fetch data if not provided via props
    useEffect(() => {
        if (shouldFetchData) {
            fetchNotifications();
        }
    }, [shouldFetchData]);

    const fetchNotifications = async () => {
        setIsLoading(true);
        try {
            const response = await fetch('/api/admin/notifications');
            if (response.ok) {
                const data = await response.json();
                setLocalNotifications(data.notifications || []);
                setUnreadCount(data.unreadCount || 0);
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const getLevelIcon = (level: string) => {
        switch (level) {
            case 'success':
                return <CheckCircle className="w-5 h-5 text-green-500" />;
            case 'warning':
                return <AlertTriangle className="w-5 h-5 text-yellow-500" />;
            case 'error':
                return <AlertCircle className="w-5 h-5 text-red-500" />;
            default:
                return <Info className="w-5 h-5 text-blue-500" />;
        }
    };

    const getLevelColor = (level: string) => {
        switch (level) {
            case 'success':
                return 'border-l-green-500 bg-green-50';
            case 'warning':
                return 'border-l-yellow-500 bg-yellow-50';
            case 'error':
                return 'border-l-red-500 bg-red-50';
            default:
                return 'border-l-blue-500 bg-blue-50';
        }
    };

    const markAsRead = async (notificationId: string) => {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                setLocalNotifications(prev => 
                    prev.map(notif => 
                        notif.id === notificationId 
                            ? { ...notif, read_at: new Date().toISOString() }
                            : notif
                    )
                );
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    };

    const handleAction = (notification: AdminNotification) => {
        if (notification.data.action_url) {
            // Mark as read first
            markAsRead(notification.id);
            
            // Navigate to the action URL
            router.visit(notification.data.action_url);
        }
    };

    const formatTime = (timestamp: string) => {
        const date = new Date(timestamp);
        const now = new Date();
        const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
        return date.toLocaleDateString();
    };

    const unreadNotifications = localNotifications.filter(n => !n.read_at);
    const readNotifications = localNotifications.filter(n => n.read_at);

    if (isLoading && shouldFetchData) {
        return (
            <div className="flex justify-center py-8">
                <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
            </div>
        );
    }

    return (
        <div className="relative">
            {/* Notification Bell */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
            >
                <Bell className="w-6 h-6" />
                {unreadCount > 0 && (
                    <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {/* Notification Dropdown */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto">
                    <div className="p-4 border-b border-gray-200">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Notifications
                            </h3>
                            <button
                                onClick={() => setIsOpen(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div className="p-2">
                        {/* Unread Notifications */}
                        {unreadNotifications.length > 0 && (
                            <div className="mb-4">
                                <h4 className="text-sm font-medium text-gray-700 mb-2 px-2">
                                    Unread ({unreadNotifications.length})
                                </h4>
                                {unreadNotifications.map((notification) => (
                                    <NotificationItem
                                        key={notification.id}
                                        notification={notification}
                                        onMarkAsRead={markAsRead}
                                        onAction={handleAction}
                                        getLevelIcon={getLevelIcon}
                                        getLevelColor={getLevelColor}
                                        formatTime={formatTime}
                                    />
                                ))}
                            </div>
                        )}

                        {/* Read Notifications */}
                        {readNotifications.length > 0 && (
                            <div>
                                <h4 className="text-sm font-medium text-gray-700 mb-2 px-2">
                                    Earlier ({readNotifications.length})
                                </h4>
                                {readNotifications.slice(0, 5).map((notification) => (
                                    <NotificationItem
                                        key={notification.id}
                                        notification={notification}
                                        onMarkAsRead={markAsRead}
                                        onAction={handleAction}
                                        getLevelIcon={getLevelIcon}
                                        getLevelColor={getLevelColor}
                                        formatTime={formatTime}
                                        isRead={true}
                                    />
                                ))}
                            </div>
                        )}

                        {localNotifications.length === 0 && (
                            <div className="text-center py-8 text-gray-500">
                                <Bell className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                <p>No notifications yet</p>
                            </div>
                        )}
                    </div>

                    {localNotifications.length > 0 && (
                        <div className="p-3 border-t border-gray-200">
                            <button
                                onClick={() => router.visit('/admin/notifications')}
                                className="w-full text-center text-sm text-blue-600 hover:text-blue-800 font-medium"
                            >
                                View All Notifications
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

interface NotificationItemProps {
    notification: AdminNotification;
    onMarkAsRead: (id: string) => void;
    onAction: (notification: AdminNotification) => void;
    getLevelIcon: (level: string) => React.ReactNode;
    getLevelColor: (level: string) => string;
    formatTime: (timestamp: string) => string;
    isRead?: boolean;
}

const NotificationItem: React.FC<NotificationItemProps> = ({
    notification,
    onMarkAsRead,
    onAction,
    getLevelIcon,
    getLevelColor,
    formatTime,
    isRead = false
}) => {
    const [isExpanded, setIsExpanded] = useState(false);

    return (
        <div className={`mb-2 border-l-4 ${getLevelColor(notification.level)} rounded-r-lg p-3 hover:bg-gray-50 transition-colors`}>
            <div className="flex items-start space-x-3">
                <div className="flex-shrink-0 mt-0.5">
                    {getLevelIcon(notification.level)}
                </div>
                
                <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between">
                        <h5 className={`text-sm font-medium ${isRead ? 'text-gray-600' : 'text-gray-900'}`}>
                            {notification.data.title}
                        </h5>
                        <div className="flex items-center space-x-2">
                            <span className="text-xs text-gray-500">
                                {formatTime(notification.created_at)}
                            </span>
                            {!isRead && (
                                <button
                                    onClick={() => onMarkAsRead(notification.id)}
                                    className="text-gray-400 hover:text-gray-600"
                                    title="Mark as read"
                                >
                                    <Eye className="w-4 h-4" />
                                </button>
                            )}
                        </div>
                    </div>
                    
                    <p className={`text-sm mt-1 ${isRead ? 'text-gray-500' : 'text-gray-700'}`}>
                        {notification.data.message}
                    </p>

                    {/* Special handling for new user registrations */}
                    {notification.type === 'new_user_registration' && (
                        <div className="mt-2 p-2 bg-blue-50 rounded border border-blue-200">
                            <div className="text-xs text-blue-800">
                                <p><strong>New User:</strong> {notification.data.new_user_name}</p>
                                {notification.data.new_user_email && (
                                    <p><strong>Email:</strong> {notification.data.new_user_email}</p>
                                )}
                                {notification.data.new_user_phone && (
                                    <p><strong>Phone:</strong> {notification.data.new_user_phone}</p>
                                )}
                                {notification.data.registration_time && (
                                    <p><strong>Registered:</strong> {notification.data.registration_time}</p>
                                )}
                            </div>
                        </div>
                    )}

                    {notification.data.action_text && notification.data.action_url && (
                        <button
                            onClick={() => onAction(notification)}
                            className="mt-2 text-xs text-blue-600 hover:text-blue-800 font-medium"
                        >
                            {notification.data.action_text} →
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AdminNotifications;
