# Missing portraits — batch 261

This batch prepares **100 additional portraits** for 100 existing prisoner records, with no overlap with batches 258, 259 or 260. [All 100 previews, source credits and identity notes](../photos/CREDITS-batch261.md).

The set contains 28 individually cataloged Freedom Rider booking photographs, 30 crops identified by the Fort Douglas photograph’s numbered handwritten key, six individually named Swarthmore archival photographs, and 36 other sourced portraits. The Fort Douglas crops are small historical images, generally around 60–100 pixels wide; their original grain, faded detail and handwritten marks remain. They were not enlarged or reconstructed.

Seventy outputs use exact rectangular crops, including one full-frame GIF-to-PNG conversion for William MacQueen. Pixel equality with the original crop was checked for every output. The other thirty downloaded images retain their original bytes. Source originals for the crops are retained with hashes, dimensions and crop boxes in the payload. The full Fort Douglas group and its handwritten key are also included for review.

Initial encyclopedia image-index lookups covered the 6,318 records remaining after excluding the 66 portraits prepared in batches 258–260. These were discovery lookups, not exhaustive investigations of all 6,318 people. Candidate photographs were then checked against archival captions, the prisoner’s existing context, and the image itself. Wrong-name results, party flags, unnamed groups and unresolved name-key matches were excluded. Duplicate/alias records such as Carol Silver, Lucy G. Branham, Phillip Caplowitz and Howard Wilbur Moore were not counted as extra people; this batch does not merge or edit those records.

Rights and source terms are documented individually. Several sources are copyrighted without an open license. MDAH asks for a publication-permission form; that has not been submitted. The Gordon Kahl public-domain claim has not been independently established, and Roy Tyler’s intermediary source has conflicting license labels. The credits preserve these qualifications and do not claim blanket publication or merchandise permission.

The idempotent runner validates every profile’s ID, slug and name, plus the image format and hash, before any write. It preserves any already attached photo, fills only empty photo fields, guards concurrent changes and storage collisions, and clears the prisoner API and museum caches after a successful write. No biographies, cases, dates or websites are changed. No production write is performed during preparation.

## Validation

- 100 distinct existing profile identities; no overlap with batches 258–260.
- All selected originals and output crops visually reviewed.
- Image formats, hashes, crop bounds and retained-pixel equality checked.
- Bash and PHP syntax checks passed.
- Read-only live application preview reported `Would update rows: 100` and `B261-OK`; all 100 selected records still had empty photo fields. No production data or portrait storage writes were performed.

## Deployment after merge

Apply earlier pending batches in order, then:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-261.sh --dry-run
sudo -u www-data bash database/data/run-batch-261.sh
```

## Profiles

- A. D. King
- Abraham Bassford
- Alexander Mattahan Anderson
- Alfred Buzzi
- Alice Gram
- Antonio Colonna
- Benjamin Breger
- Bert S. Brush
- Bobby Rush
- Bruce Friedrich
- Carol Ruth Silver
- Carolyn Yvonne Reed
- David Eichel
- David J. Dennis Sr.
- Delbert Tibbs
- Edward William Kale
- Elizabeth Porter Wyckoff
- Elizabeth Selden Rogers
- Emile de Antonio
- Eric Rungnar Platin
- Francis Steiner
- Francisco Carreón
- Frank Minnick
- Frank Moser
- Fred J. Muhlke
- Garrett Jones
- George Mizo
- Glenda Jean Gaither
- Goldie Watson
- Gordon Wendell Kahl
- Gustus Wortsmann
- Gwendolyn Green
- Harold Andrews
- Harry M. Clave
- Heath Cliff Rush
- Helene Dorothy Wilson
- Henry Jager
- Henry Monsky
- Henry Pollack
- Henry Schneider
- Howard Moore
- Isabelo de los Reyes
- Jackie Hudson
- Jacob Conroy
- Jacob E. Haugen
- Jacob Wortsmann
- James B. McNamara
- Jan Leighton Triggs
- Jane Ellen Rosett
- Janice Louise Rogers
- Jean Catherine Thompson
- Jesse James Harris
- John E. Downey
- John J. Ballam
- Joseph Carter
- Joseph Caruso
- Joseph Ettor
- Josephine Collins
- Josephine Johnson
- Julius Eichel
- Knud M. Lassen
- L. J. C. Daniels
- Larry Pinkney
- Lawrence Williamson
- Leon J. Kamin
- Leslie Word
- Lewis J. Gergotz
- Lucy Gwynne Branham
- Luis Gutiérrez
- Margaret Winonah Beamer
- Mark Comfort
- Marvin Allen Davidov
- Matthew Walker Jr.
- Michael J. Audain
- Mugo Gatheru
- Ned Cobb
- Nehanda Abiodun
- Noah Jos. Blair
- Obadiah Lee Simms
- Peter Harry Stoner
- Philip Caplovitz
- Práxedis G. Guerrero
- Raymond B. Randolph Jr.
- Rexford Powell
- Richard LeRoy Gleason
- Robert Wesby
- Roy Horlacher
- Roy Tyler
- Sam Cutler
- Samuel Austin Worcester
- Samuel Sterenstein
- Sander Maki
- Thomas P. Moran
- Walter H. Clark
- William Breidert
- William C. Sandberg
- William E. Harbour
- William Jasmagy
- William MacQueen
- William Monroe Trotter
