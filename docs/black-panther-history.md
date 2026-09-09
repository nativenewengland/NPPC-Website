# Black Panther Party history page

Original NPPC feature at `/black-panther-party`, linked from Learn More navigation and the existing Panther topic. The page presents six selected cities and 13 milestones; it does not reproduce UW's dataset or claim complete chapter coverage. Historical summaries and per-entry sources are in `resources/data/black-panther-history.json`.

The map uses approximate city-center coordinates, not historical building locations. Leaflet 1.9.4 is integrity-pinned, with credited OpenStreetMap tiles; city controls and the complete timeline work without map tiles, and all histories remain readable without JavaScript. Filters are reflected in the URL and restored on browser back/forward.

## Image provenance

`public/images/black-panther-history/olympia-1969.jpg` is a byte-for-byte copy of the existing `database/data/topic-photos/black-panther-party.jpg`. Seattle Panthers at the Washington State Capitol, 28 February 1969. Washington State Archives, State Governors' Negative Collection; shared by CIR Online. [Source and license record](https://commons.wikimedia.org/wiki/File:Black_Panther_demonstration.jpg), [CC BY 2.0](https://creativecommons.org/licenses/by/2.0/). No asset editing; CSS frames the photograph responsively. Visible credit and license links appear on the page. Profile images are read from existing published prisoner records and are not copied or reassigned.

## Data and deployment

No database writes, migration or numbered data batch. The controller reads editorial JSON and existing prisoner names/photos through the model's public scope. Missing or under-review records are omitted, and profiles without photos have a text fallback. Deploy through the ordinary merge and `git pull origin main` workflow. No Vue rebuild is required for this Blade feature.

## Editorial date precision

Seattle chapter closing dates differ among UW accounts; the legacy milestone deliberately uses “Late 1970s.” The numeric 1977 value only places that broad date in the later-years filter. The Seattle clinic milestone says “By 1970” because Kurt Schaefer documents its development beginning in 1969. City markers represent chapter areas: the Sacramento and Olympia protests are identified in their event text, not plotted as incidents at Oakland or Seattle.

## Validation

- Rendered the new Blade page in memory using the existing Laravel installation and a read-only query of nine public prisoner profiles; no production files or records were deployed.
- PHP syntax checks passed for the new controller, navigation helper and routes; JavaScript syntax and Git whitespace checks passed.
- Browser checks at desktop and 390px mobile widths: city/theme/search/period filtering, empty state, reset, restored URL filters, native platform expansion, all nine profile photos, and no horizontal overflow.
- Preview fixtures without Leaflet retained city filtering; fixtures without scripts exposed all six city histories and 13 milestones, with native platform details still usable.
- This is a focused rendered-page check, not a full PHPUnit run; local PHP/vendor dependencies are unavailable.
