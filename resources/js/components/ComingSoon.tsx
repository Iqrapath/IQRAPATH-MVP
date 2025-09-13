import React from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Clock, Construction } from 'lucide-react';

interface ComingSoonProps {
    title?: string;
    description?: string;
}

export default function ComingSoon({ 
    title = "Coming Soon", 
    description = "This page is currently under development. Please check back later!" 
}: ComingSoonProps) {
    return (
        <div className="min-h-[400px] flex items-center justify-center p-8">
            <Card className="w-full max-w-md text-center">
                <CardContent className="p-8">
                    <div className="flex justify-center mb-4">
                        <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                            <Construction className="h-8 w-8 text-orange-600" />
                        </div>
                    </div>
                    
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">
                        {title}
                    </h2>
                    
                    <p className="text-gray-600 mb-6">
                        {description}
                    </p>
                    
                    <div className="flex items-center justify-center text-sm text-gray-500">
                        <Clock className="h-4 w-4 mr-2" />
                        <span>Under Development</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
