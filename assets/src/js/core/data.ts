/**
 * Check if a dataset property of an element is true
 * @param element The element to check
 * @param name The value within the dataset
 * @param defaultValue The fallback value
 */
export function dataBool(
  element: HTMLElement,
  name: string,
  defaultValue = false,
): boolean {
  const value = element.dataset[name];

  if ( value === undefined ) return defaultValue;

  return value === '' || value === 'true' || value === '1';
}

/**
 * Get a string value from an element's dataset
 * @param element The element to check
 * @param name The value within the dataset
 * @param defaultValue The fallback value
 */
export function dataStr(
  element: HTMLElement,
  name: string,
  defaultValue = '',
): string {
  const value = element.dataset[name];

  if ( value === undefined ) return defaultValue;

  return value;
}

/**
 * Get an integer value from an element's dataset
 * @param element The element to check
 * @param name The value within the dataset
 * @param defaultValue The fallback value
 */
export function dataInt(
  element: HTMLElement,
  name: string,
  defaultValue = 0,
): number {
  const value = element.dataset[name];

  if ( value === undefined ) return defaultValue;

  const parsed = parseInt( value,10 );

  return isNaN( parsed ) ? defaultValue : parsed;
}