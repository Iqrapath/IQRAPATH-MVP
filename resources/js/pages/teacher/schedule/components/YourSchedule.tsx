/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=200-7399&t=barbCY4Jn7yoOuNr-0
 * Export: Your Schedule component with Upcoming and Past sessions
 * 
 * EXACT SPECS FROM FIGMA:
 * - Tab navigation: "Upcoming Session (3)" and "Past Sessions"
 * - Calendar component with month/year display
 * - Session cards with time, subject, teacher, and join button
 * - Clean white background with teal accents
 */
import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import UpcomingSessions from './UpcomingSessions';
import PastSessions from './PastSessions';
import axios from 'axios';

interface YourScheduleProps {
    // Add any props needed for data fetching
}

export default function YourSchedule({}: YourScheduleProps) {
    const [activeTab, setActiveTab] = useState<'upcoming' | 'past'>('upcoming');
    const [upcomingCount, setUpcomingCount] = useState(0);
    const [pastCount, setPastCount] = useState(0);

    // Fetch session counts
    useEffect(() => {
        const fetchCounts = async () => {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                // Fetch upcoming sessions count
                const upcomingResponse = await axios.get('/teacher/sessions/upcoming', {
                    withCredentials: true,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (upcomingResponse.data.success) {
                    setUpcomingCount(upcomingResponse.data.sessions?.length || 0);
                }

                // Fetch past sessions count
                const pastResponse = await axios.get('/teacher/sessions/past', {
                    withCredentials: true,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (pastResponse.data.success) {
                    setPastCount(pastResponse.data.sessions?.length || 0);
                }
            } catch (error) {
                console.error('Error fetching session counts:', error);
            }
        };

        fetchCounts();
    }, []);

    return (
        <div className="space-y-6 mt-10">
            {/* Tab Navigation */}
            <div className="flex space-x-8">
                <button
                    onClick={() => setActiveTab('upcoming')}
                    className={`text-sm font-medium transition-all duration-200 relative inline-block ${
                        activeTab === 'upcoming'
                            ? 'text-teal-600 font-semibold'
                            : 'text-gray-500 hover:text-gray-700'
                    }`}
                >
                    Upcoming Session ({upcomingCount})
                    {activeTab === 'upcoming' && (
                        <div className="absolute -bottom-1 left-0 h-0.5 bg-teal-600 w-full"></div>
                    )}
                </button>
                <button
                    onClick={() => setActiveTab('past')}
                    className={`text-sm font-medium transition-all duration-200 relative ${
                        activeTab === 'past'
                            ? 'text-teal-600 font-semibold'
                            : 'text-gray-500 hover:text-gray-700'
                    }`}
                >
                    Past Sessions ({pastCount})
                    {activeTab === 'past' && (
                        <div className="absolute -bottom-1 left-0 w-full h-0.5 bg-teal-600"></div>
                    )}
                </button>
            </div>

            {/* Content */}
            <div className="mt-6">
                {activeTab === 'upcoming' ? (
                    <UpcomingSessions />
                ) : (
                    <PastSessions />
                )}
            </div>
        </div>
    );
}
