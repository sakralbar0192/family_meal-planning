export async function startDemoMocks(): Promise<void> {
  const { setupWorker } = await import('msw/browser');
  const { demoHandlers } = await import('./handlers');
  const worker = setupWorker(...demoHandlers);
  await worker.start({
    onUnhandledRequest: 'bypass',
    quiet: true,
    serviceWorker: {
      url: `${import.meta.env.BASE_URL}mockServiceWorker.js`,
    },
  });
}
