# Black Panther Party location map

Researched and checked 9 September 2026. This catalog supplies the map at
`/black-panther-party`; it is separate from the six editorial city histories
and 13 milestones in `black-panther-history.json`.

## Coverage

71 distinct cities/localities: 70 in the United States (27 states and the
District of Columbia), plus the International Section in Algiers. This replaces
the original six-marker selection with a marker for every documented location
in this catalog. It is not a claim that all 71 organizations existed
simultaneously, held formal chapter charters, or exhaust every historical
Panther presence.

The starting inventory includes **every city/locality in the two-page 1971
affiliate directory**, including inactive organizations: 61 distinct places
after grouping the New York neighborhood entries under New York City and Watts
under Los Angeles. The neighborhood names remain in the JSON. Separate
municipalities such as Berkeley, Richmond, Compton and Mount Vernon have their
own markers.

Nine additional U.S. locations are supported by participant and chapter
histories: Marin City, Chattanooga, Nashville, Atlanta, Tacoma, Walla Walla,
Eugene, Carbondale and High Point. Algiers is the original Party's International
Section, not a separate organization inspired by the Panthers.

The display classifications are 30 chapters, 29 affiliated organizing centers,
four locations described collectively as chapters/branches, three branches,
two prison organizations, national headquarters, a Party office, and the
International Section. Labels describe the cited historical evidence; they are
not a judgment about an organization's legitimacy. In particular, the catalog
does not silently promote an NCCF or community information center to a chapter.

## Sources and editorial decisions

- [1971 directory, printed pp. 88–89, PDF pp. 100–101](https://blackfreedom.proquest.com/wp-content/uploads/2020/09/blackpanther18.pdf#page=100):
  *Gun-Barrel Politics: The Black Panther Party, 1966–1971*, House Committee on
  Internal Security. Both scanned table pages were visually checked. Its
  footnotes describe a partial compilation of Party newspaper directories,
  subsequent articles, and police reports. This catalog uses its place and
  organization columns, not the report's political allegations.
- [The Black Panther, 27 June 1970, printed p. 22, PDF p. 21](https://washingtonareaspark.com/wp-content/uploads/2020/06/1970-06-27-black-panther-vol-4-no-30.pdf#page=21):
  the Party's own recognized chapter/branch/NCCF and community-center directory
  provides a second citation for locations it lists.
- [Founding member Elbert “Big Man” Howard's retrospective](https://sfbayview.com/2016/10/revolutionary-50-years-of-the-black-panther-party/):
  supports the four additional locations labeled “Chapter / branch.” The account
  names chapters and branches together, so these records do not infer a more
  precise organizational designation.
- [Seattle Black Panther Party Interpretive Center](https://www.seattleblackpantherpartyinterpretivecenter.org/):
  documents Tacoma and Eugene branches and the Walla Walla prison organization.
- [Illinois Chapter Historical Preservation Society](https://ilbpp.org/major-operational-sites):
  documents Carbondale's NCCF office and a Rockford branch headquarters.
- [State Library of North Carolina](https://www.ncpedia.org/black-panther-party):
  supports High Point's satellite community information center. Its account of
  unsuccessful recognition in Charlotte is not treated as proof of a chartered
  Panther chapter there.
- [UC Berkeley's International Section photographs](https://digicoll.lib.berkeley.edu/record/286335):
  archival documentation for the Algiers location.

NCCF means National Committee to Combat Fascism. The directory records changing
chapter/NCCF designations in Denver, Detroit, Omaha, Jersey City, Seattle,
Baltimore and Washington; their entries explicitly explain this. San Quentin
and Walla Walla are labeled prison organizations. Houston's 1969 Party office
listing is preserved as an office, without conflating it with People's Party II.

## Coordinates and maintenance

69 U.S. locations use the internal points in the
[2025 Census place Gazetteer](https://www2.census.gov/geo/docs/maps-data/data/gazetteer/2025_Gazetteer/2025_Gaz_place_national.zip).
Each retains its Census GEOID. Indianapolis and Nashville use the Census
balance-place names. San Quentin is not a Census place in this file.

San Quentin (37.94126, -122.4848) and Algiers (36.73225, 3.08746) use
[GeoNames](https://www.geonames.org/) locality coordinates; each record includes
its coordinate-source link. GeoNames attribution and the
[CC BY 4.0 license](https://creativecommons.org/licenses/by/4.0/) appear on the
page. All points are approximate modern community locations, not historical
office addresses, arrest sites, or prison buildings.

To extend the catalog, add a unique stable ID, city/state/country, verified
coordinates, a supported organization label, a short original note and an
`evidence` reference to the shared source registry. Disambiguate same-named
cities by state, as with Cleveland, Mississippi and Cleveland, Ohio. The map,
selector, directory, detail panels and counts are all generated from this one
catalog. Preserve existing IDs used by shared URLs and the timeline.

## Validation

- 71 unique IDs and city/state pairs, valid coordinate ranges, all evidence
  references resolving, 61 directory-backed locations, and exact Gazetteer
  coordinate/GEOID matches for the 69 applicable records.
- The original six histories and all 13 milestones are unchanged.
- The local Blade template rendered against the existing Laravel runtime in
  memory, with SQLite `query_only` enabled. Controller and JavaScript syntax and
  Git whitespace checks passed. No production content or database rows changed.
- Browser checks confirmed 71 markers and 72 selector options including All
  cities; all marker bounds fit in the overview at desktop and 390px mobile
  sizes. The page has no horizontal overflow at the tested mobile size.
- Tested Baltimore, both Clevelands, San Quentin, Algiers, Berkeley and High
  Point; source panels, selected markers, directory focus, shared city/theme/
  period/search parameters, back navigation, reset, and existing Seattle
  timeline filtering worked.
- Without Leaflet, location selection and sources still work. Without scripts,
  all 71 source panels remain readable and directory links have valid query and
  fragment targets. There are no new timeline entries for cities without
  researched milestones; the empty-state copy explains this distinction.

The full PHPUnit suite was not run: the local checkout has no PHP/vendor
runtime. Preview console inspection also showed an existing shared-navigation
error outside this feature; map interactions passed independently.

Deployment is the normal merge and pull of `main`. The controller reads this
JSON from the repository; no data batch, migration, database cache operation,
or frontend build is needed.
