function handleOpen(mainContainer, window) {
  if (!mainContainer || !window) {
    return;
  }

  const windowScroll = window.scrollY;

  const yOffset = -70;
  const y = mainContainer.getBoundingClientRect().top + windowScroll + yOffset;

  // Scroll to the top of the main container
  window.scrollTo({ top: y, behavior: 'instant' });

  // Return the old scroll position
  return windowScroll;
}
