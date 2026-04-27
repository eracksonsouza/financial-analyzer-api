import { useEffect, useState } from 'react'
import './RuleCard.css'

interface Rule {
  needs_pct: number
  wants_pct: number
  debt_pct: number
}

interface Props { rule: Rule }

interface BarProps {
  label: string
  sublabel: string
  actual: number
  ideal: number
  color: string
  delay: number
  animated: boolean
}

function RuleBar({ label, sublabel, actual, ideal, color, delay, animated }: BarProps) {
  const over  = actual > ideal
  const width = animated ? Math.min(actual, 100) : 0

  const colorClass = over
    ? 'rule-bar--over'
    : color === 'var(--amber)'
      ? 'rule-bar--amber'
      : color === 'var(--red)'
        ? 'rule-bar--red'
        : 'rule-bar--green'

  const delayClass = `rule-delay-${Math.round(delay * 10)}`

  return (
    <div className="rule-row">
      <div className="rule-row-header">
        <div>
          <span className="rule-row-label">{label}</span>
          <span className="rule-row-sub">{sublabel}</span>
        </div>
        <div className="rule-row-values">
          <span className={over ? 'rule-value rule-value--over' : 'rule-value rule-value--ok'}>
            {actual}%
          </span>
          <span className="rule-ideal-tag">≤{ideal}%</span>
        </div>
      </div>
      <div className="rule-track">
        <svg viewBox="0 0 100 6" className="rule-track-svg" preserveAspectRatio="none">
          <rect x="0" y="1" width="100" height="4" rx="2" className="rule-track-bg" />
          <rect
            x="0"
            y="1"
            width={width}
            height="4"
            rx="2"
            className={`rule-track-fill ${colorClass} ${delayClass}`}
          />
          <line x1={ideal} y1="0" x2={ideal} y2="6" className="rule-track-marker" />
        </svg>
      </div>
    </div>
  )
}

export function RuleCard({ rule }: Props) {
  const [animated, setAnimated] = useState(false)

  useEffect(() => {
    const t = setTimeout(() => setAnimated(true), 100)
    return () => clearTimeout(t)
  }, [])

  return (
    <div className="rule-card">
      <div className="chart-title">regra 50/30/20</div>

      <RuleBar
        label="necessidades"
        sublabel="moradia · alimentação · transporte · saúde"
        actual={rule.needs_pct}
        ideal={50}
        color="var(--green)"
        delay={0.1}
        animated={animated}
      />
      <RuleBar
        label="desejos"
        sublabel="lazer · educação · outros"
        actual={rule.wants_pct}
        ideal={30}
        color="var(--amber)"
        delay={0.2}
        animated={animated}
      />
      <RuleBar
        label="dívidas"
        sublabel="parcelas e empréstimos"
        actual={rule.debt_pct}
        ideal={20}
        color="var(--red)"
        delay={0.3}
        animated={animated}
      />
    </div>
  )
}
