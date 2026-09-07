export function onKey(
  key: string,
  handler: ( event: KeyboardEvent ) => void,
) {
  return ( event: KeyboardEvent ) => {
    if ( event.key === key ) handler( event );
  }
}