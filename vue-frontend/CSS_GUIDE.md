# Custom CSS Design System Guide

## Overview
This project uses a custom CSS design system with reusable utility classes and components, similar to Tailwind but customized for your needs.

## CSS Variables (Design Tokens)

### Colors
```css
--emerald: #50C878
--teal: #008080
--primary: #6366f1
--secondary: #8b5cf6
--success: #10b981
--warning: #f59e0b
--danger: #ef4444
--info: #3b82f6
```

### Gray Scale
```css
--gray-50 to --gray-900 (from lightest to darkest)
```

### Spacing Scale
```css
--spacing-xs: 4px
--spacing-sm: 8px
--spacing-md: 16px
--spacing-lg: 24px
--spacing-xl: 32px
--spacing-2xl: 48px
```

### Border Radius
```css
--radius-sm: 6px
--radius-md: 8px
--radius-lg: 12px
--radius-xl: 16px
```

## Utility Classes

### Flexbox
```html
<div class="flex flex-col items-center justify-between gap-md">
  <!-- Content -->
</div>
```

**Available classes:**
- `flex`, `flex-col`, `flex-row`
- `items-center`, `items-start`, `items-end`
- `justify-center`, `justify-between`, `justify-start`, `justify-end`
- `flex-1`, `flex-wrap`
- `gap-sm`, `gap-md`, `gap-lg`

### Spacing
```html
<div class="p-lg m-md">Content</div>
```

**Padding:** `p-xs`, `p-sm`, `p-md`, `p-lg`, `p-xl`
**Margin:** `m-xs`, `m-sm`, `m-md`, `m-lg`, `m-xl`

### Typography
```html
<h1 class="text-2xl font-bold text-center">Title</h1>
<p class="text-base text-gray-600">Description</p>
```

**Sizes:** `text-sm`, `text-base`, `text-lg`, `text-xl`, `text-2xl`
**Weight:** `font-bold`, `font-semibold`, `font-medium`
**Align:** `text-center`, `text-left`, `text-right`

### Colors
```html
<span class="text-primary">Primary text</span>
<div class="bg-gray-100">Background</div>
```

**Text colors:** `text-gray-500`, `text-primary`, `text-success`, `text-warning`, `text-danger`
**Backgrounds:** `bg-white`, `bg-gray-50`, `bg-gray-100`, `bg-primary`

### Border & Shadows
```html
<div class="rounded-lg shadow-md">Card</div>
```

**Radius:** `rounded-sm`, `rounded-md`, `rounded-lg`, `rounded-xl`, `rounded-full`
**Shadows:** `shadow-sm`, `shadow-md`, `shadow-lg`, `shadow-xl`

## Component Classes

### Button
```html
<button class="btn">Default</button>
<button class="btn secondary">Secondary</button>
<button class="btn primary">Primary</button>
<button class="btn danger">Danger</button>
<button class="btn outline">Outline</button>
```

### Card
```html
<div class="card">
  <h3>Card Title</h3>
  <p>Card content</p>
</div>
```

### Input
```html
<input type="text" class="input" placeholder="Enter text">
```

### Badge
```html
<span class="badge success">Active</span>
<span class="badge warning">Pending</span>
<span class="badge danger">Error</span>
<span class="badge info">Info</span>
```

## Animations

### Classes
```html
<div class="animate-fade-in">Fades in</div>
<div class="animate-slide-up">Slides up</div>
<div class="animate-spin">Spins</div>
```

### Transitions
```html
<button class="transition">Smooth transition</button>
<button class="transition-fast">Fast transition</button>
```

## Example: Building a Card with Utilities

**Before (Custom classes):**
```vue
<template>
  <div class="my-card">
    <h3 class="card-title">Title</h3>
    <p class="card-description">Description</p>
  </div>
</template>

<style scoped>
.my-card {
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}
.card-description {
  color: #6b7280;
  font-size: 0.875rem;
}
</style>
```

**After (Using utilities):**
```vue
<template>
  <div class="card p-lg shadow-md rounded-lg">
    <h3 class="text-xl font-bold m-sm">Title</h3>
    <p class="text-sm text-gray-500">Description</p>
  </div>
</template>
```

## Best Practices

1. **Use variables for colors:** Always use CSS variables instead of hardcoded colors
2. **Combine utilities:** Mix utility classes for common patterns
3. **Create components:** For complex, reusable UI, use component classes
4. **Scoped styles:** Use `<style scoped>` for component-specific styles
5. **Transitions:** Add `transition` class for smooth interactions

## Quick Reference Cheat Sheet

```html
<!-- Flexbox Layout -->
<div class="flex items-center justify-between gap-md">

<!-- Spacing -->
<div class="p-lg m-md">

<!-- Typography -->
<h1 class="text-2xl font-bold text-center text-primary">

<!-- Card -->
<div class="card shadow-lg rounded-xl">

<!-- Button -->
<button class="btn primary">

<!-- Badge -->
<span class="badge success">

<!-- Animation -->
<div class="animate-slide-up transition">
```

## Migration Tips

When refactoring existing code:
1. Replace inline padding/margin with utility classes
2. Use flex utilities instead of custom flex CSS
3. Apply shadow and rounded utilities to cards
4. Use badge component for status indicators
5. Add transitions for better UX

Happy coding! 🎨
