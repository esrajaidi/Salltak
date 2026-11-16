export async function boundedWait(promise, milliseconds, fallback) {
  let timer;
  try {
    return await Promise.race([
      Promise.resolve(promise).catch(() => fallback),
      new Promise(resolve => { timer = setTimeout(() => resolve(fallback), milliseconds); }),
    ]);
  } finally { clearTimeout(timer); }
}
