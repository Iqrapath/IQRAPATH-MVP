import { useState, useEffect } from 'react';

interface OnlineUser {
    id: number;
    name: string;
    avatar: string | null;
    role: string;
}

interface UseOnlineStatusReturn {
    onlineUsers: OnlineUser[];
    onlineUserIds: number[];
    isOnline: (userId: number) => boolean;
    isConnected: boolean;
}

export const useOnlineStatus = (): UseOnlineStatusReturn => {
    const [onlineUsers, setOnlineUsers] = useState<OnlineUser[]>([]);
    const [isConnected, setIsConnected] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined' || !window.Echo) {
            console.warn('Laravel Echo not available for presence channel');
            return;
        }

        try {
            const channel = window.Echo.join('presence-online');

            channel
                .here((users: OnlineUser[]) => {
                    console.log('Users currently online:', users);
                    setOnlineUsers(users);
                    setIsConnected(true);
                })
                .joining((user: OnlineUser) => {
                    console.log('User joined:', user);
                    setOnlineUsers((prev) => {
                        // Avoid duplicates
                        if (prev.some((u) => u.id === user.id)) {
                            return prev;
                        }
                        return [...prev, user];
                    });
                })
                .leaving((user: OnlineUser) => {
                    console.log('User left:', user);
                    setOnlineUsers((prev) => prev.filter((u) => u.id !== user.id));
                })
                .error((error: any) => {
                    console.error('Presence channel error:', error);
                    setIsConnected(false);
                });

            return () => {
                console.log('Leaving presence channel');
                window.Echo.leave('presence-online');
                setIsConnected(false);
            };
        } catch (error) {
            console.error('Failed to join presence channel:', error);
            setIsConnected(false);
        }
    }, []);

    const onlineUserIds = onlineUsers.map((u) => u.id);
    const isOnline = (userId: number) => onlineUserIds.includes(userId);

    return {
        onlineUsers,
        onlineUserIds,
        isOnline,
        isConnected,
    };
};
