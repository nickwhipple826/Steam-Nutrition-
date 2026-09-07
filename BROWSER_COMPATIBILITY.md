# Browser Compatibility

## Minimum Requirements

This theme requires modern browsers with ES2015+ support.

### Supported Browsers

✅ **Chrome/Edge**: Version 88+ (January 2021)  
✅ **Firefox**: Version 78+ (June 2020)  
✅ **Safari**: Version 14+ (September 2020)  
✅ **Mobile Safari (iOS)**: Version 14+  
✅ **Chrome Android**: Version 88+

### Not Supported

❌ **Internet Explorer 11** - No support  
❌ **Legacy Edge (EdgeHTML)** - Use Chromium Edge instead

## JavaScript Features Used

The theme uses modern JavaScript features without polyfills:

- **ES Modules** (`import`/`export`)
- **Arrow functions** (`() => {}`)
- **Template literals** (backticks)
- **Destructuring** (`const { x } = obj`)
- **Optional chaining** (`?.`)
- **Nullish coalescing** (`??`)
- **`const`/`let`** block scoping
- **Classes** and class fields
- **`async`/`await`**
- **DOM APIs**: `dataset`, `querySelector`, `closest`, etc.

## TypeScript Compilation

TypeScript is compiled to:
- **Target**: `esnext` (modern browsers)
- **Module**: `nodenext` (ES modules)

See `tsconfig.json` for complete configuration.

## CSS Requirements

- **CSS Grid** support
- **CSS Custom Properties** (CSS variables)
- **`clamp()`** for fluid typography (if using Utopia)
- **Modern selectors**: `:is()`, `:where()` (optional, depending on your SCSS)

## Third-Party Libraries

All bundled via Vite:

- **Swiper 12.x** - Modern browser support
- **focus-trap 8.x** - ES6+ required

## Adding Polyfills (Optional)

If you need to support older browsers, add polyfills:

```bash
npm install core-js regenerator-runtime
```

Update `vite.config.js`:
```js
export default defineConfig({
  build: {
    target: 'es2015', // Lower target
    polyfillModulePreload: true,
  }
});
```

Import in `main.ts`:
```typescript
import 'core-js/stable';
import 'regenerator-runtime/runtime';
```

## Testing Browser Support

Recommended testing targets:
1. Latest Chrome (primary development)
2. Latest Firefox
3. Latest Safari (especially for iOS)
4. Chrome on Android

## Analytics

Check your WordPress site's Google Analytics to see what browsers your actual visitors use before deciding on support levels.

## Progressive Enhancement

The theme follows progressive enhancement principles:
- Core content is accessible without JavaScript
- JavaScript enhances the experience
- Components fail gracefully if initialization fails
- ARIA attributes provide accessibility baseline