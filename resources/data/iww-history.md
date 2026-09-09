# IWW movement history

Public route: `/iww`. Linked from Learn More and the published Industrial Workers of the World topic.

## Scope and sources

The page presents 21 selected milestones, 1905–1924, across 16 reference cities. It connects those summaries to published NPPC topics and 11 existing prisoner profiles. The accompanying JSON contains the source registry, event summaries, city coordinates and profile order. Each event links to its supporting sources. The University of Washington's [IWW History Project](https://depts.washington.edu/iww/) inspired the page; its prose and datasets have not been copied into this repository. Short original summaries also draw on IWW documents, the National Park Service, Library of Congress, California Office of Historic Preservation, Utah History to Go and Arizona archival material.

Events retain the precision supported by their sources. Period filters use the event's starting year. The Wheatland confrontation is dated August 3, **1913**, following California's historic landmark record; the UW strike overview's 1915 date is not used. National raids and the regional timber strike are represented by Chicago and Spokane respectively, with their wider geography explained on the page. The timeline is a selection, not an exhaustive incident or local-union census.

City coordinates are the internal points in the [2025 U.S. Census place Gazetteer](https://www2.census.gov/geo/docs/maps-data/data/gazetteer/2025_Gazetteer/2025_Gaz_place_national.zip). Gazetteer GEOIDs are retained in the JSON. These are community reference points, not exact incident or historical office addresses; Butte uses the consolidated Butte–Silver Bow place. All 16 coordinates were matched back to the Gazetteer.

## Complete historical locals map

The separate local-union section embeds the existing Tableau Public visualization linked by [UW's map page](https://depts.washington.edu/iww/map_locals.shtml), credited to Arianne Hermida and James Gregory. UW's directory describes 928 locals, branches and district councils in more than 350 US and Canadian cities and towns. Recorded active periods are evidence ranges, not necessarily founding or closing dates.

The iframe loads only when requested. It remains hosted by its publisher; no Tableau dataset or map screenshot is copied. Its controls are separate from the NPPC timeline. Direct links to the original map and the complete readable directory remain available without JavaScript or if the external service fails. On narrow screens the embedded visualization scrolls within its own container.

## Existing NPPC records

`IwwHistoryController` reads existing prisoner names, slugs and photos and includes only published topic links. Missing profile/topic records are omitted rather than producing broken links. The affiliation count is queried at request time using the existing full IWW affiliation and its legacy spelling. It is not a fixed research total. Existing profile photos are used as assigned; a neutral placeholder appears where no photo is stored.

This feature does not change biographies, cases, affiliations or database rows. It requires no data batch or migration. Deploy through the normal application code deployment. The image credit is in `public/images/iww-history/CREDITS.md` and appears on the page.

## Interaction and verification

Timeline city, theme, period and search filters are combined and stored in the URL. Reload and browser back restore the selected filters. All 16 event-city markers remain on the map for context. Marker clicks select a city; expand/collapse acts on visible events only. Empty results provide a reset action. Native event details and all source links remain readable without JavaScript, and timeline controls continue to work if Leaflet is unavailable. This route hides the shared fade-transition overlay so a failed or disabled script cannot conceal the rendered history.

Verified on 2026-09-09: PHP syntax for the controller, routes and navigation helper; Blade compilation and rendering against read-only live records; JavaScript syntax; JSON source references, chronological order and coordinate matches; 21 rendered milestones, 16 published topic targets and 11 existing profiles. Browser checks covered combined filters, empty/reset, marker selection, expansion, URL restoration, the live Tableau map and desktop/phone layouts. The shared site navigation script emits an existing `globalNavDropdowns` null error in the local preview; the IWW interactions function independently.
