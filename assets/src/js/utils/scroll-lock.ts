let lockCount = 0;
let previousHtmlOverflow = '';
let previousBodyOverflow = '';

export function lockScroll(): void {
  if ( lockCount === 0 ) {
    previousHtmlOverflow = document.documentElement.style.overflow;
    previousBodyOverflow = document.body.style.overflow;

    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }

  lockCount++;
}

export function unlockScroll(): void {
  lockCount--;

  if ( lockCount > 0 ) return;

  document.documentElement.style.overflow = previousHtmlOverflow;
  document.body.style.overflow = previousBodyOverflow;
}