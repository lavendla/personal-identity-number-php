# Provenance — Norwegian fixtures

Where every Norwegian parsing fixture came from, and why no bearer can exist
for any of them.

The rule, from `CLAUDE.md`: a fixture that must be **valid** comes from an
official published test range or it does not go in — with two named
exceptions. This corpus draws on **Exception 1**: a number the registry could
never have issued at all, because no bearer can exist.

## Why Tenor is not the source

Skatteetaten's *Tenor testdatasøk* is Norway's own synthetic test population,
and would have been the natural source. It is unreachable for this project —
its search requires a Norwegian eID — so every number here is *generated*,
not vendored, from the same convention Tenor's own population is built on. The
full argument, the anchor number the generator is proved against, and the
terms-of-use position for every page consulted live in
[`../../sources/norway-synthetic/README.md`](../../sources/norway-synthetic/README.md).
This file cites that argument; it does not restate it.

## The `+80` (and `+40`) argument, in short

Skatteetaten, on Tenor's synthetic population:

> Personene har syntetiske fødsels- og d-nummer, der vi har plusset på +80 på
> måneden.

Digdir states the identical rule independently, for its own test users:

> må disse opprettes med +80 i månedsfeltet i fødselsnummeret

**A written month of 81–92 is not a calendar month, so no bearer can exist.**
Every fixture below carries `"source": "unissuable"` on that basis. A
D-number's day carries its own, separate `+40` offset
(Folkeregisterhåndboken § 2-2, Annet ledd), which does not by itself make a
number unissuable — real D-numbers are written with that offset — but every
D-number fixture here is *also* synthetic, so the `+80` month argument applies
to it the same as to a fødselsnummer.

## Every fixture is `unissuable`

Every fixture in `national-identity-number.json` and `d-number.json` carries
`"source": "unissuable"`, including the two that assert a failure for a reason
*other* than the synthetic month (`…-fails-its-checksum` and
`…-unissuable-individual-year-combination-…`): the written month on both is
still 81–92, so the synthetic-marker argument holds regardless of the second,
independent reason each fixture's `provenanceNote` also states.

## Table

Every fixture in this corpus, and why its number has no bearer — one row per
fixture rather than per number, so the two pairs of fixtures that share a
number (`05847510147` and `09867652318`, each used twice for different
purposes) are each individually traceable, matching
`spec/fixtures/foreign/PROVENANCE.md`'s own per-fixture format. `century_series`
is `generated.csv`'s own label — see
[`../../sources/norway-synthetic/README.md`](../../sources/norway-synthetic/README.md#files)
for what each one means. Every D-number row shows both offsets: the day
carries `+40` structurally, on every D-number, whether or not the row is also
being used to demonstrate the boundary.

| Fixture | Number | `century_series` | Why it has no bearer |
|---|---|---|---|
| `…-refuses-a-synthetic-value-without-the-flag` | `05847510147` | `fnr-499-000` | Month 84 (04 + 80) |
| `…-parses-a-synthetic-value-with-the-flag-allowed` | `05847510147` | `fnr-499-000` | Month 84 (04 + 80) |
| `…-female-individual-number-is-even` | `18918825033` | `fnr-499-000` | Month 91 (11 + 80) |
| `…-individual-series-500-749-lower-boundary` | `09867652318` | `fnr-749-500` | Month 86 (06 + 80) |
| `…-individual-series-500-749-upper-boundary` | `22829974987` | `fnr-749-500` | Month 82 (02 + 80) |
| `…-leap-day-in-a-leap-year-parses` | `29822477753` | `fnr-999-500` | Month 82 (02 + 80) |
| `…-same-day-and-month-in-a-non-leap-year-is-impossible` | `29822351350` | `fnr-999-500` | Month 82 (02 + 80); *also* decodes to 29 February 2023, which does not exist |
| `…-individual-series-900-999-resolves-nineteen-hundreds` | `14855590124` | `fnr-999-900` | Month 85 (05 + 80) |
| `…-individual-series-900-999-female` | `27909995820` | `fnr-999-900` | Month 90 (10 + 80) |
| `…-unissuable-individual-year-combination-is-not-an-identity-number` | `16834560287` | `fnr-unissuable-500-899` | Month 83 (03 + 80); *also* individnummer 602 against year 45 matches no century-table row at all |
| `no-national-identity-number-fails-its-checksum` | `18918825032` | derived from `fnr-499-000` | Month 91 (11 + 80); *also* one digit changed from `18918825033` so the check digits (correctly `33`) no longer match — see below |
| `no-national-identity-number-and-d-number-share-individual-and-year-but-not-century` | `09867652318` | `fnr-749-500` | Month 86 (06 + 80) |
| `no-d-number-keeps-both-offsets-in-its-canonical-form` | `41836515143` | `dnr-000-499` | Day 41 (01 + 40); month 83 (03 + 80) |
| `no-d-number-day-boundary-at-seventy-one` | `71879930051` | `dnr-000-499` | Day 71 (31 + 40); month 87 (07 + 80) |
| `no-d-number-individual-series-500-999-female` | `55891067886` | `dnr-500-999` | Day 55 (15 + 40); month 89 (09 + 80) |
| `no-d-number-individual-series-500-999-male` | `65912384300` | `dnr-500-999` | Day 65 (25 + 40); month 91 (11 + 80) |
| `no-d-number-and-national-identity-number-share-individual-and-year-but-not-century` | `49867652301` | `dnr-500-999` | Day 49 (09 + 40); month 86 (06 + 80) |
| `no-d-number-resolves-to-a-future-birth-date` | `49867652301` | `dnr-500-999` | Day 49 (09 + 40); month 86 (06 + 80); *also* resolves to 2076-06-09, after the 2026-08-16 reference date this fixture uses |

`18918825032` is the one number in this table not read directly from
`generated.csv`: it is `18918825033` (the `fnr-499-000` row for individual
250) with its final digit changed, so that the check digits computed for
`189188250` — correctly `33` — no longer match. It still carries the same
month offset as every other row, so it is unissuable for both reasons at
once, and the digit change is recorded exactly, the same way
`spec/fixtures/foreign/PROVENANCE.md` records each of its constructed check
digits against the correct one.

## The century-table pair

`no-national-identity-number-and-d-number-share-individual-and-year-but-not-century`
and `no-d-number-and-national-identity-number-share-individual-and-year-but-not-century`
are the reason `generated.csv` gained a fourteenth row. Individnummer 523 and
two-digit year 76 resolve to 1876 under Folkeregisterhåndboken § 2-2-1's
fødselsnummer table (`09867652318`) and to 2076 under § 2-2-2's D-number table
(`49867652301`) — the identical digits, 200 years apart, because the two
sections define genuinely different century tables rather than one table
applied two ways. A fødselsnummer parser and a D-number parser that
accidentally shared a century table would produce a wrong-but-plausible birth
date for every D-number in the 500–999 individnummer series, and neither
runtime's own unit tests for the two tables would catch it, because unit
tests do not cross-check one runtime against the other. This pair is what
does.

Both fixtures set `referenceDate` to `2090-01-01` rather than this corpus's
usual `2026-08-16`, because `49867652301` resolves to 2076-06-09 — a future
birth date against 2026, which would fail the parse before the century-table
divergence the pair exists to pin was ever reached. `09867652318`'s own
result, 1876, is unaffected by which reference date is used, since 1876
precedes both.

That avoided failure is itself pinned directly: `no-d-number-resolves-to-a-future-birth-date`
uses the same digits at this corpus's usual `2026-08-16` reference date and
asserts `FutureBirthDate`. `d-number.json`'s `centuryTable.description` names
`FutureBirthDate` as the mechanism that catches an uncapped far-future
D-number year — the § 2-2-2 table's `500-999` row has no year cap, unlike
fødselsnummer's equivalent row — and this is the fixture that exercises it,
rather than leaving the claim untested.

## No `ImplausibleBirthDate` fixture — an absence by proof

Norway's minimum birth year is 1854, declared in
`spec/schemes/no/national-identity-number.json`'s `minimumBirthYear` for
symmetry with Sweden and Denmark, and stated there as
**deliberately inert**: the fødselsnummer century table's own floor is 1854
(individnummer 500–749, year 54–99 → the 1800s), so no structurally valid
number — synthetic or otherwise — can resolve to a year before it. There is
no fixture asserting `ImplausibleBirthDate` for Norway because none can be
constructed, which is an absence by proof rather than by oversight, exactly
as Denmark's own `minimumBirthYearNote` records for its equivalent floor.

## Why the Swedish provenance gate is silent here, re-checked for this corpus

`spec/fixtures/foreign/PROVENANCE.md` already argues that
`tools/check-provenance.mjs` cannot match an eleven-digit Norwegian number —
its ten- and twelve-digit patterns are guarded by digit-boundary lookarounds
that stop a shorter or longer slice being taken out of an eleven-digit run —
but that argument was made about nine recognize-only fixtures, none of which
had to be checksum-valid. This corpus is different: every fødselsnummer and
D-number fixture here *is* a checksum-valid, shape-correct eleven-digit
number, which is a stronger case for the gate to have an opinion about, not a
weaker one. It was re-run rather than assumed:

```
$ npm run provenance:check
provenance:check — no unexplained Luhn-valid Swedish-shaped numbers found.
$ npm run provenance:check:history
provenance:check --history — no unexplained Luhn-valid Swedish-shaped numbers found in any reachable commit.
```

Both passed with this corpus present, staged and committed. The reasoning is
unchanged from the foreign file: eleven digits cannot match a ten- or
twelve-digit pattern past the digit-boundary lookarounds, synthetic month or
not. **Passing a check you were never subject to is not evidence of anything
on its own**; the evidence is the table above.
