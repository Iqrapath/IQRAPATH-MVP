import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MessageDropdown } from '../message-dropdown';

// Mock the use-messages hook
const mockUseMessages = vi.fn();
vi.mock('@/hooks/use-messages', () => ({
  useMessages: () => mockUseMessages(),
}));

vi.mock('@inertiajs/react', () => ({
  Link: ({ children, href }: any) => <a href={href}>{children}</a>,
}));

vi.mock('@/hooks/use-initials', () => ({
  useInitials: () => (name: string) => name.substring(0, 2).toUpperCase(),
}));

vi.mock('date-fns', () => ({
  formatDistanceToNow: () => '2 minutes ago',
}));

describe('MessageDropdown Error States', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  /**
   * Test auth error state rendering
   * Requirements: 2.1, 2.2, 6.5
   */
  it('renders auth error state when user is not authenticated', async () => {
    const user = userEvent.setup();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: false,
      error: null,
      authError: {
        type: 'auth',
        message: 'Unauthenticated',
        code: 'AUTH_REQUIRED',
      },
      permissionError: null,
      isAuthenticated: false,
      fetchMessages: vi.fn(),
      markAllAsRead: vi.fn(),
      clearErrors: vi.fn(),
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify auth error is displayed
    await waitFor(() => {
      expect(screen.getByText('Authentication Required')).toBeInTheDocument();
      expect(screen.getByText('Unauthenticated')).toBeInTheDocument();
    });

    // Verify Log In button is present
    expect(screen.getByText('Log In')).toBeInTheDocument();

    // Verify Dismiss button is present
    expect(screen.getByText('Dismiss')).toBeInTheDocument();
  });

  /**
   * Test permission error state rendering
   * Requirements: 2.1, 2.2, 6.5
   */
  it('renders permission error state when user lacks permissions', async () => {
    const user = userEvent.setup();
    const mockClearErrors = vi.fn();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: false,
      error: null,
      authError: null,
      permissionError: {
        type: 'permission',
        message: 'You are not authorized to view these conversations',
        code: 'AUTHORIZATION_FAILED',
        details: {
          reason: 'not_participant',
        },
      },
      isAuthenticated: true,
      fetchMessages: vi.fn(),
      markAllAsRead: vi.fn(),
      clearErrors: mockClearErrors,
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify permission error is displayed
    await waitFor(() => {
      expect(screen.getByText('Access Denied')).toBeInTheDocument();
      expect(screen.getByText('You are not authorized to view these conversations')).toBeInTheDocument();
    });

    // Verify reason is displayed
    expect(screen.getByText(/Reason: not_participant/)).toBeInTheDocument();

    // Verify Retry button is present
    expect(screen.getByText('Retry')).toBeInTheDocument();

    // Verify Dismiss button is present
    const dismissButton = screen.getByText('Dismiss');
    expect(dismissButton).toBeInTheDocument();

    // Test dismiss functionality
    await user.click(dismissButton);
    expect(mockClearErrors).toHaveBeenCalled();
  });

  /**
   * Test empty state rendering
   * Requirements: 2.1, 2.2, 6.5
   */
  it('renders empty state when no conversations are available', async () => {
    const user = userEvent.setup();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: false,
      error: null,
      authError: null,
      permissionError: null,
      isAuthenticated: true,
      fetchMessages: vi.fn(),
      markAllAsRead: vi.fn(),
      clearErrors: vi.fn(),
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify empty state is displayed
    await waitFor(() => {
      expect(screen.getByText('No messages')).toBeInTheDocument();
      expect(
        screen.getByText('Start a conversation with someone to see messages here.')
      ).toBeInTheDocument();
    });
  });

  /**
   * Test retry functionality
   * Requirements: 2.1, 2.2, 6.5
   */
  it('calls fetchMessages when retry button is clicked', async () => {
    const user = userEvent.setup();
    const mockFetchMessages = vi.fn();
    const mockClearErrors = vi.fn();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: false,
      error: new Error('Network error'),
      authError: null,
      permissionError: null,
      isAuthenticated: true,
      fetchMessages: mockFetchMessages,
      markAllAsRead: vi.fn(),
      clearErrors: mockClearErrors,
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify error is displayed
    await waitFor(() => {
      expect(screen.getByText('Error Loading Messages')).toBeInTheDocument();
      expect(screen.getByText('Network error')).toBeInTheDocument();
    });

    // Click retry button
    const retryButton = screen.getByText('Retry');
    await user.click(retryButton);

    // Verify clearErrors and fetchMessages were called
    expect(mockClearErrors).toHaveBeenCalled();
    await waitFor(() => {
      expect(mockFetchMessages).toHaveBeenCalled();
    });
  });

  /**
   * Test loading state rendering
   * Requirements: 2.1, 2.2, 6.5
   */
  it('renders loading skeleton when messages are loading', async () => {
    const user = userEvent.setup();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: true,
      error: null,
      authError: null,
      permissionError: null,
      isAuthenticated: true,
      fetchMessages: vi.fn(),
      markAllAsRead: vi.fn(),
      clearErrors: vi.fn(),
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify loading skeletons are displayed
    await waitFor(() => {
      const skeletons = document.querySelectorAll('.animate-pulse');
      expect(skeletons.length).toBeGreaterThan(0);
    });
  });

  /**
   * Test that errors take precedence over loading state
   * Requirements: 2.1, 2.2, 6.5
   */
  it('shows error state instead of loading when both are present', async () => {
    const user = userEvent.setup();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: true,
      error: new Error('Failed to load'),
      authError: null,
      permissionError: null,
      isAuthenticated: true,
      fetchMessages: vi.fn(),
      markAllAsRead: vi.fn(),
      clearErrors: vi.fn(),
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify error is shown, not loading skeleton
    await waitFor(() => {
      expect(screen.getByText('Error Loading Messages')).toBeInTheDocument();
    });

    // Verify loading skeleton is not shown
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toBe(0);
  });

  /**
   * Test auth error takes precedence over permission error
   * Requirements: 2.1, 2.2, 6.5
   */
  it('shows auth error when both auth and permission errors are present', async () => {
    const user = userEvent.setup();

    mockUseMessages.mockReturnValue({
      messages: [],
      unreadCount: 0,
      isLoading: false,
      error: null,
      authError: {
        type: 'auth',
        message: 'Not authenticated',
        code: 'AUTH_REQUIRED',
      },
      permissionError: {
        type: 'permission',
        message: 'Not authorized',
        code: 'FORBIDDEN',
      },
      isAuthenticated: false,
      fetchMessages: vi.fn(),
      markAllAsRead: vi.fn(),
      clearErrors: vi.fn(),
    });

    render(<MessageDropdown />);

    // Click to open dropdown
    const button = screen.getByRole('button');
    await user.click(button);

    // Verify auth error is shown
    await waitFor(() => {
      expect(screen.getByText('Authentication Required')).toBeInTheDocument();
    });

    // Verify permission error is not shown
    expect(screen.queryByText('Access Denied')).not.toBeInTheDocument();
  });
});
