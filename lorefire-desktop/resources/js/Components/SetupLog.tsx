import React, { useEffect, useRef } from 'react'

export function SetupLog({ log, emptyHint }: { log?: string | null; emptyHint?: string }) {
  const ref = useRef<HTMLPreElement>(null)

  useEffect(() => {
    if (ref.current) {
      ref.current.scrollTop = ref.current.scrollHeight
    }
  }, [log])

  const text = (log ?? '').trim()
  if (!text && !emptyHint) {
    return null
  }

  return (
    <pre
      ref={ref}
      className="mt-3 max-h-44 overflow-auto text-[10px] font-mono leading-relaxed p-2 rounded border"
      style={{
        background: 'var(--color-abyss)',
        color: 'var(--color-text-dim)',
        borderColor: 'var(--color-border)',
        whiteSpace: 'pre-wrap',
        wordBreak: 'break-word',
      }}
    >
      {text || emptyHint}
    </pre>
  )
}
