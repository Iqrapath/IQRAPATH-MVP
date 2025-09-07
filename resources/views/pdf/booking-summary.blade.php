<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Summary - {{ $booking->booking_uuid }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #14B8A6;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #14B8A6;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #f8f9fa;
            color: #14B8A6;
            font-size: 18px;
            font-weight: bold;
            padding: 10px 15px;
            margin: 0 0 15px 0;
            border-left: 4px solid #14B8A6;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #555;
            padding: 8px 15px 8px 0;
            width: 30%;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #333;
        }
        
        .rating {
            color: #fbbf24;
        }
        
        .notes-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .notes-content {
            font-style: italic;
            color: #666;
            margin: 0;
        }
        
        .no-notes {
            color: #999;
            font-style: italic;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .url-link {
            color: #14B8A6;
            word-break: break-all;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-ongoing {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status-upcoming {
            background-color: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Class Summary Report</h1>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <!-- Class Information -->
    <div class="section">
        <h2 class="section-title">Class Information</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Booking ID:</div>
                <div class="info-value">{{ $booking->booking_uuid }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Subject:</div>
                <div class="info-value">{{ $subject?->name ?? 'Unknown Subject' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teacher:</div>
                <div class="info-value">
                    @if(is_object($teacher) && $teacher->name)
                        Ustadh {{ $teacher->name }}
                    @elseif(is_string($teacher) && $teacher !== 'Unknown Teacher')
                        Ustadh {{ $teacher }}
                    @else
                        Teacher Name
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Student:</div>
                <div class="info-value">{{ $student->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ strtolower($booking->status) }}">
                        {{ ucfirst($booking->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Details -->
    <div class="section">
        <h2 class="section-title">Session Details</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($sessionDate)->format('F j, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Time:</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($startTime)->format('g:i A') }} - 
                    {{ \Carbon\Carbon::parse($endTime)->format('g:i A') }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Duration:</div>
                <div class="info-value">{{ $duration ?? 60 }} minutes</div>
            </div>
            <div class="info-row">
                <div class="info-label">Platform:</div>
                <div class="info-value">{{ $meetingPlatform ?? 'Zoom' }}</div>
            </div>
            @if($meetingUrl)
            <div class="info-row">
                <div class="info-label">Meeting URL:</div>
                <div class="info-value">
                    <span class="url-link">{{ $meetingUrl }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Notes from Teacher -->
    <div class="section">
        <h2 class="section-title">Notes from Teacher</h2>
        <div class="notes-section">
            @if($teacherNotes)
                <p class="notes-content">{{ $teacherNotes }}</p>
            @else
                <p class="no-notes">
                    No notes from 
                    @if(is_object($teacher) && $teacher->name)
                        ustadh {{ $teacher->name }}
                    @elseif(is_string($teacher) && $teacher !== 'Unknown Teacher')
                        ustadh {{ $teacher }}
                    @else
                        teacher
                    @endif
                    available at the moment.
                </p>
            @endif
        </div>
    </div>

    <!-- Student Review -->
    <div class="section">
        <h2 class="section-title">Rate & Review</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Rating:</div>
                <div class="info-value">
                    @if($rating > 0)
                        <span class="rating">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $rating)
                                    ★
                                @else
                                    ☆
                                @endif
                            @endfor
                            ({{ $rating }}/5)
                        </span>
                    @else
                        <span class="no-notes">No rating provided</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="notes-section">
            @if($studentReview)
                <p class="notes-content">{{ $studentReview }}</p>
            @else
                <p class="no-notes">No review provided yet.</p>
            @endif
        </div>
    </div>

    <!-- Student Notes -->
    <div class="section">
        <h2 class="section-title">Student Notes</h2>
        <div class="notes-section">
            @if($studentNotes)
                <p class="notes-content">{{ $studentNotes }}</p>
            @else
                <p class="no-notes">No personal notes added yet.</p>
            @endif
        </div>
    </div>

    <!-- Additional Resources -->
    @if($recordingUrl || $materialsUrl)
    <div class="section">
        <h2 class="section-title">Additional Resources</h2>
        <div class="info-grid">
            @if($recordingUrl)
            <div class="info-row">
                <div class="info-label">Recording:</div>
                <div class="info-value">
                    <span class="url-link">{{ $recordingUrl }}</span>
                </div>
            </div>
            @endif
            @if($materialsUrl)
            <div class="info-row">
                <div class="info-label">Materials:</div>
                <div class="info-value">
                    <span class="url-link">{{ $materialsUrl }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated by IQRAPATH - Islamic Education Platform</p>
        <p>For any questions, please contact our support team.</p>
    </div>
</body>
</html>
