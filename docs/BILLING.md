# Billing calculation

`App\Services\InvoiceCalculator` is a pure PHP calculator. It receives
immutable DTOs containing a billing period, plan snapshots for each segment,
and usage units attributed to each segment. It performs no database or cache
access. All amounts are integer paise and all rounding uses integer
round-half-up arithmetic.

The period and segment boundaries are inclusive calendar days after clipping
segments to the invoice period. The base amount for a segment is:

```text
round_half_up(plan.base_price_paise * segment_days / cycle_days)
```

The prorated allowance is:

```text
round_half_up(plan.included_units * segment_days / cycle_days)
```

Overage is:

```text
max(0, segment_usage - prorated_allowance) * overage_rate_paise_per_unit
```

The calculator emits one base line and one overage line per segment. It rounds
the total base numerator once and assigns the final rounding remainder to the
last segment's base line, so the line-item sum exactly equals the rounded total.

## Worked example

Assume a 31-day January cycle:

- Customer starts on January 11 on Starter: base `10,000` paise, allowance
  `100` units, overage `10` paise/unit.
- Customer upgrades on January 21 to Growth: base `20,000` paise, allowance
  `300` units, overage `5` paise/unit.
- Starter usage is 60 units over 10 days.
- Growth usage is 400 units over 11 days.

The Starter base line is:

```text
10,000 * 10 / 31 = 3,225.806... => 3,226 paise
```

Its allowance is `100 * 10 / 31 = 32` units after half-up rounding, so it has
`60 - 32 = 28` overage units, costing `28 * 10 = 280` paise.

The Growth base line is:

```text
20,000 * 11 / 31 = 7,096.774... => 7,097 paise
```

Its allowance is `300 * 11 / 31 = 106` units, so it has `400 - 106 = 294`
overage units, costing `294 * 5 = 1,470` paise.

The invoice contains four lines:

| Type | Plan | Amount (paise) |
|---|---|---:|
| Base | Starter | 3,226 |
| Overage | Starter | 280 |
| Base | Growth | 7,097 |
| Overage | Growth | 1,470 |

Subtotal: **12,073 paise**.

Prices are snapshots supplied to the calculator. Invoice generation must load
authoritative prices from the database, not from `PlanPricingCache`.
