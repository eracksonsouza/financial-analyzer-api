import { useEffect, useState } from 'react'
import type { Metrics } from '../services/api'
import './DonutChart.css'

const LABELS: Record<string, string> = {
  moradia:     'moradia',
  alimentacao: 'alimentação',
  transporte:  'transporte',
  saude:       'saúde',
  lazer:       'lazer',
  educacao:    'educação',
  dividas:     'dívidas',
  outros:      'outros',
}

const R = 62
const C = 2 * Math.PI * R

interface Props { metrics: Metrics }

export function DonutChart({ metrics }: Props) {
  const [hovered, setHovered] = useState<string | null>(null)
  const [animated, setAnimated] = useState(false)

  useEffect(() => {
    const t = setTimeout(() => setAnimated(true), 60)
    return () => clearTimeout(t)
  }, [])

  const ratios = metrics.expense_ratios
  const totalRatios = Object.values(ratios).reduce((s, v) => s + v, 0)

  const entries = Object.entries(ratios)
    .filter(([, v]) => v > 0)
    .sort(([, a], [, b]) => b - a)

  let cumAngle = -90

  const segments = entries.map(([key, ratio], idx) => {
    const fraction = ratio / (totalRatios || 1)
    const segAngle = fraction * 360
    const segLen   = fraction * C
    const GAP      = Math.min(3, segLen * 0.15)
    const startAngle = cumAngle
    cumAngle += segAngle
    return { key, ratio, segLen, GAP, startAngle, idx }
  })

  return (
    <div className="donut-wrap">
      <div className="chart-title">distribuição das despesas</div>
      <div className="donut-body">
        <div className="donut-svg-wrap">
          <svg viewBox="0 0 200 200" width="180" height="180">
            {segments.map(({ key, segLen, GAP, startAngle, idx }) => {
              const isHovered = hovered === key
              const dash = animated ? Math.max(0, segLen - GAP) : 0
              return (
                <circle
                  key={key}
                  cx={100} cy={100} r={R}
                  fill="none"
                  strokeWidth={isHovered ? 26 : 22}
                  strokeDasharray={`${dash} ${C}`}
                  transform={`rotate(${startAngle} 100 100)`}
                  strokeLinecap="butt"
                  className={[
                    'donut-segment',
                    `cat-${key}`,
                    `delay-${idx}`,
                    hovered && !isHovered ? 'donut-segment--dimmed' : '',
                  ].join(' ')}
                  onMouseEnter={() => setHovered(key)}
                  onMouseLeave={() => setHovered(null)}
                />
              )
            })}

            <circle cx={100} cy={100} r={51} fill="var(--bg)" />

            <text x={100} y={95} textAnchor="middle" fill="var(--text-3)" fontSize="9" fontFamily="DM Mono, monospace" letterSpacing="0.05em">
              {hovered ? (LABELS[hovered] ?? hovered) : 'poupança'}
            </text>
            <text x={100} y={112} textAnchor="middle" fill="var(--text)" fontSize="18" fontWeight="500" fontFamily="DM Mono, monospace">
              {hovered ? `${ratios[hovered]}%` : `${metrics.savings_rate}%`}
            </text>
          </svg>
        </div>

        <div className="donut-legend">
          {entries.map(([key, ratio]) => (
            <div
              key={key}
              className={`legend-item${hovered === key ? ' legend-item--active' : ''}`}
              onMouseEnter={() => setHovered(key)}
              onMouseLeave={() => setHovered(null)}
            >
              <span className={`legend-dot cat-${key}`} />
              <span className="legend-label">{LABELS[key] ?? key}</span>
              <span className="legend-pct">{ratio}%</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
