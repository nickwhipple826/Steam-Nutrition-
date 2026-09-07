export function qs<T extends Element>(
  selector: string,
  scope: ParentNode = document,
): T | null {
  return scope.querySelector( selector ) as T | null;
}

export function qsa<T extends Element>(
  selector: string,
  scope: ParentNode = document,
): T[] {
  return Array.from( scope.querySelectorAll( selector ) ) as T[]
}