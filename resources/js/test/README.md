# Frontend Testing with Vitest

This project uses Vitest for frontend testing of React/TypeScript components and utilities.

## Running Tests

```bash
# Run tests in watch mode (interactive)
npm test

# Run tests once (CI mode)
npm run test:run

# Run tests with UI
npm run test:ui

# Run tests with coverage
npm run test:coverage
```

## Test Structure

Tests are located alongside the code they test in `__tests__` directories:

```
resources/js/
├── lib/
│   ├── authorization.ts
│   └── __tests__/
│       └── authorization.test.ts
├── hooks/
│   ├── use-messages.ts
│   └── __tests__/
│       └── use-messages.test.ts
└── components/
    ├── message/
    │   ├── message-dropdown.tsx
    │   └── __tests__/
    │       └── message-dropdown.test.tsx
```

## Writing Tests

### Unit Tests (Utilities/Helpers)

```typescript
import { describe, it, expect } from 'vitest';
import { myFunction } from '../myFunction';

describe('myFunction', () => {
    it('should do something', () => {
        const result = myFunction('input');
        expect(result).toBe('expected output');
    });
});
```

### Component Tests

```typescript
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MyComponent from '../MyComponent';

describe('MyComponent', () => {
    it('renders correctly', () => {
        render(<MyComponent />);
        expect(screen.getByText('Hello')).toBeInTheDocument();
    });

    it('handles user interaction', async () => {
        const user = userEvent.setup();
        render(<MyComponent />);
        
        await user.click(screen.getByRole('button'));
        expect(screen.getByText('Clicked')).toBeInTheDocument();
    });
});
```

### Testing Hooks

```typescript
import { describe, it, expect } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { useMyHook } from '../useMyHook';

describe('useMyHook', () => {
    it('returns expected values', () => {
        const { result } = renderHook(() => useMyHook());
        
        expect(result.current.value).toBe('initial');
    });

    it('updates on action', async () => {
        const { result } = renderHook(() => useMyHook());
        
        result.current.updateValue('new');
        
        await waitFor(() => {
            expect(result.current.value).toBe('new');
        });
    });
});
```

## Mocking

### Mocking Modules

```typescript
import { vi } from 'vitest';

// Mock entire module
vi.mock('axios');

// Mock specific function
vi.mock('../api', () => ({
    fetchData: vi.fn(() => Promise.resolve({ data: 'mocked' })),
}));
```

### Mocking API Calls

```typescript
import { vi } from 'vitest';
import axios from 'axios';

vi.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

it('fetches data', async () => {
    mockedAxios.get.mockResolvedValue({ data: { id: 1 } });
    
    const result = await fetchUser(1);
    expect(result).toEqual({ id: 1 });
});
```

## Test Coverage

View coverage report:

```bash
npm test:coverage
```

Coverage reports are generated in `coverage/` directory.

## Configuration

- **vitest.config.ts** - Main Vitest configuration
- **resources/js/test/setup.ts** - Global test setup (runs before all tests)

## Best Practices

1. **Test behavior, not implementation** - Focus on what the code does, not how it does it
2. **Use descriptive test names** - Test names should clearly describe what is being tested
3. **Arrange-Act-Assert** - Structure tests with clear setup, execution, and verification
4. **Keep tests isolated** - Each test should be independent and not rely on others
5. **Mock external dependencies** - Mock API calls, external services, etc.
6. **Test edge cases** - Don't just test the happy path

## Troubleshooting

### Tests not running

Make sure dependencies are installed:
```bash
npm install
```

### Import errors

Check that path aliases are configured correctly in `vitest.config.ts`:
```typescript
resolve: {
    alias: {
        '@': path.resolve(__dirname, './resources/js'),
    },
}
```

### DOM not available

Make sure `environment: 'jsdom'` is set in `vitest.config.ts`.

## Resources

- [Vitest Documentation](https://vitest.dev/)
- [Testing Library](https://testing-library.com/docs/react-testing-library/intro/)
- [Testing Library User Event](https://testing-library.com/docs/user-event/intro)
