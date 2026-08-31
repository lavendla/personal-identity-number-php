# Provenance — cross-country foreign fixtures

**Every fixture here asserts a failure, and there are two routes to having no
bearer.** Nine are `constructed` to fail their own country's national checksum.
Two are `unissuable` — checksum-**valid** Norwegian synthetic values carrying
Skatteetaten's `+80` month marker, which no bearer can hold either. Read a
fixture's `source` before assuming which it is.

**What they no longer demonstrate.** These began as the recognize-only corpus:
while Norway and Finland were recognized-by-shape-and-refused-by-name, a value
here resolved to `UnsupportedScheme` with `recognizedCountry()` naming the
country, and shape was the only question the package could ask. Both schemes are
real as of spec `1.0.0`, and a checksum-invalid value is exactly what a real
scheme rejects — so these values are no longer recognized as anything, and the
fixtures now assert `not-an-identity-number` with `recognizedCountry()` null.

Their ids still say `is-recognized`. A fixture id is never edited, so the names
record what they were written to pin rather than what they pin now — which is the
narrower and still useful claim that **a value no registry would accept gets the
fallback failure and no hint**, whichever country was asked.

The recognition they used to demonstrate is pinned instead by fixtures using
values a registry genuinely accepts: `fi-…-is-recognized-when-asked-as-swedish`
in `spec/fixtures/fi/`, and the synthetic Norwegian pair at the bottom of this
file. A recognition now requires a valid number, which is the whole change.

See `spec/fixtures/no/PROVENANCE.md` and `spec/fixtures/fi/PROVENANCE.md` for the
sibling corpora built the opposite way — checksum-valid on purpose, so they
resolve successfully.

## Why a constructed foreign number is permissible here

`CLAUDE.md`'s rule is about **valid** numbers: a randomly generated valid
personal identity number very likely belongs to a living person. A fixture here
never needs to be valid, because what it pins is a refusal — so it only has to be
invalid under a checksum that is implemented and real, which both countries' now
are.

So every constructed fixture here is built to **fail its own country's national
checksum**. That is the substantive claim, and it is stronger than "we made it
up": a number that fails Norway's modulus-11 or Finland's modulus-31 control
character cannot have been issued by that registry, so it has no bearer, while
still being shape-recognisable — which is exactly what the fixture needs to be.

The checksums were computed to make each number invalid, not assumed. The Finnish
algorithm was verified against DVV's own published example first: `131052-308T`,
whose control character the same code independently reproduces as `T`. An
algorithm that reproduces a published example is one whose "this is invalid"
answers can be trusted.

| Fixture | Number | Why it has no bearer |
|---|---|---|
| `no-…-is-recognized-not-supported` | `13108633528` | Norway's check digits for `131086335` are `27`; this ends `28` |
| `no-…-is-recognized-without-a-country-named` | `13108633528` | Same digits, no `issuedBy` |
| `no-…-recognition-ignores-the-date` | `31139912300` | Day 31 of month 13 **and** wrong check digits (`311399123` requires `09`) |
| `no-…-with-a-separator-is-not-recognized` | `131086-33559` | Not a Norwegian shape at all — eleven digits, no separator |
| `fi-…-is-recognized-not-supported` | `131052-308U` | Control character for `131052308` is `T` |
| `fi-…-recognizes-the-2000s-marker` | `010101A123P` | Control character for `010101123` is `N` |
| `fi-…-recognizes-a-marker-added-in-2023` | `150375Y246D` | Control character for `150375246` is `C` |
| `fi-…-in-lowercase-is-an-invalid-character` | `131052-308u` | Same invalid code, lowercase |
| `fi-…-is-recognized-when-nothing-parses-it` | `010490-998T` | Control character for `010490998` is `9` |
| `no-…-synthetic-value-is-recognized-only-when-opted-in` | `05847510147` | **Valid**, `unissuable`: month 84 is not a calendar month |
| `no-…-synthetic-value-is-not-recognized-on-default-options` | `05847510147` | Same digits, `allowSyntheticNumbers` left at its default |

**Two fixtures here are not constructed**, and both are Norwegian synthetic
values marked `unissuable` — the `no-…-synthetic-value-is-recognized-only-when-opted-in`
pair. They must be checksum-**valid**, because what they pin is that a recognition
happens at all and only when the caller opted in, which an invalid value cannot
demonstrate. Their month of 84 is Skatteetaten's `+80` marker, so no bearer can
exist regardless — `CLAUDE.md`'s Exception 1.

`fi-personal-identity-code-loses-to-a-real-danish-parse` used to be the
not-constructed fixture in this file. It moved to
`spec/fixtures/ambiguous/fi-dk.json` at spec `1.0.0`, where it belongs now that
Denmark and Finland are a genuine ambiguity pair rather than a parse beating a
recognition.

## The collision, which is now a real ambiguity

**A Finnish code whose intermediate character is `-` and whose control character
happens to be a digit is character-for-character a Danish CPR number.** Not
occasionally: the Danish display form `DDMMYY-SSSS` and the Finnish
`ddmmyy-zzzQ` are the same eleven characters when `Q` is a digit, and since
modulus-11 is not a Danish validity rule, most such codes parse as valid Danish
numbers.

**This stopped being an ordering question at spec `1.0.0`.** While Finland was
recognize-only a candidate simply outranked a recognition, so `010490-9989` was
Danish and the recognition was never consulted. Finland's scheme is real now, so
that value is valid under **both** registries and `detect()` returns two
candidates with different birth dates. The caller must name a country.

`010490-998T` — the same six digits with a letter control character — matches no
Danish shape, and is invalid in Finland too, so it is refused outright with no
hint. Where the pair used to mark the boundary between a parse and a recognition,
the ambiguity itself is now pinned by `spec/fixtures/ambiguous/fi-dk.json`.

## Why the Swedish provenance gate is silent here

`tools/check-provenance.mjs` reports nothing for any number in this file, and
that was established by construction rather than by observing a green run — the
reasoning is recorded in that file's own comment. In short: Norway's eleven
digits cannot match a ten- or twelve-digit pattern past the digit-boundary
lookarounds, and Finland's letters break every pattern that requires four digits
after a separator. The all-digit Finnish forms that *do* match are the ones
identical to Danish CPR numbers, which the Danish corpus already covers.

**This is not evidence that these fixtures have provenance.** It means the gate
was never asked about them. Their provenance is the table above.

Both `2512480000` and `251248-0000` in `spec/fixtures/dk/cpr-number.json` do
match the ten-digit pattern and are silent for the other reason: they fail
Sweden's Luhn check (`251248000` requires a final `1`), so the scanner does not
report them.
