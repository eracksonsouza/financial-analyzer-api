import { useEffect, useState } from 'react'
import { getHistory, getAnalysis } from '../services/api'
import type { HistoryItem, AnalysisResponse } from '../services/api'

interface Props {
  onSelect: (res: AnalysisResponse) => void
}

export function HistoryPanel({ onSelect }: Props) {
  const [items, setItems] = useState<HistoryItem[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    getHistory()
      .then(setItems)
      .catch(() => setError('Erro ao carregar histórico.'))
      .finally(() => setLoading(false))
  }, [])

  async function handleClick(id: number) {
    try {
      const full = await getAnalysis(id)
      onSelect(full)
    } catch {
      setError('Erro ao carregar análise.')
    }
  }

  function fmt(v: number) {
    return 'R$ ' + v.toLocaleString('pt-BR', { maximumFractionDigits: 0 })
  }

  function fmtDate(s: string) {
    return new Date(s).toLocaleDateString('pt-BR', {
      day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
  }

  if (loading) {
    return (
      <div className="loading">
        <div className="spinner" />
        carregando histórico...
      </div>
    )
  }

  if (error) return <p className="error-msg">{error}</p>

  if (items.length === 0) {
    return (
      <p className="history-empty">
        nenhuma análise salva ainda.
      </p>
    )
  }

  return (
    <div className="history-list">
      {items.map(item => (
        <div key={item.id} className="history-item" onClick={() => handleClick(item.id)}>
          <div>
            <div className="history-income">{fmt(item.income)}</div>
            <div className="history-date">{fmtDate(item.created_at)}</div>
          </div>
          <div className="history-meta">
            <span
              className={`score-badge ${item.metrics.savings_rate >= 20 ? 'score-badge--good' : item.metrics.savings_rate >= 5 ? 'score-badge--warn' : 'score-badge--bad'}`}
            >
              {item.metrics.savings_rate}% poupança
            </span>
            <span className="score-badge">{item.metrics.health_score}/100</span>
          </div>
        </div>
      ))}
    </div>
  )
}
