# Batch634: San Quentin original custody and actual release

## Johnny Spain

Court p539 gives actual state-prison term start. UPI establishes March 1988 release; remove unsupported March14 day precision. Existing arrest date has not been independently resolved and is not changed in this batch.

{
  "incarceration_date": "1967-05-05 00:00:00",
  "release_date": "1988-03-01 00:00:00",
  "sentence": "Life on the underlying murder conviction; the federal court records the state-prison term beginning May 5, 1967. Released on bail in March 1988, pending proceedings on the San Quentin Six conviction. That conviction was overturned in 1986; the reversal was upheld in August 1989. The exact physical release day is not established by the cited report.",
  "date_precision": "{\"incarceration_date\":\"day\",\"release_date\":\"month\"}"
}

https://clearinghouse-umich-production.s3.amazonaws.com/media/doc/2925.pdf
https://www.upi.com/amp/Archives/1989/08/22/Court-upholds-reversal-of-Johnny-Spain-conviction/4681619761600/

## Luis Talamantez

Court p540 places original sentence service in1965; some later summaries say1966, so retain court-supported year with no invented month/day. AP August22 says he leftFriday. August20,1971 is neither original admission nor supported arrest and precedes the incident. Expand case label to reflect continuous underlying custody.

{
  "arrest_date": null,
  "incarceration_date": "1965-01-01 00:00:00",
  "release_date": "1976-08-20 00:00:00",
  "charges": "Underlying armed-robbery convictions; later San Quentin Six prosecution, acquitted in August 1976.",
  "sentence": "The federal court records two consecutive five-years-to-life terms beginning in 1965. Subsequently detained in the San Quentin Adjustment Center and prosecuted in the San Quentin Six case; acquitted August 12, 1976. Contemporary Associated Press reporting confirms physical release Friday, August 20, 1976. The original arrest day and admission month remain unresolved.",
  "date_precision": "{\"incarceration_date\":\"year\",\"release_date\":\"day\"}"
}

https://clearinghouse-umich-production.s3.amazonaws.com/media/doc/2925.pdf
https://gateway.okhistory.org/ark:/67531/metadc2550547/m1/8/

## Willie Tate

Court p540 and contemporary Panther January25 p5 corroborate January14,1975 bail release. Court p541 contains an apparent1974 typo; preceding page and contemporary report resolve it.1965 marks documented adult term, not claimed beginning of all custody. Remove unsupported1971arrest and1976release.

{
  "arrest_date": null,
  "incarceration_date": "1965-01-01 00:00:00",
  "release_date": "1975-01-14 00:00:00",
  "charges": "1965 assault-with-a-deadly-weapon conviction while already in custody; later San Quentin Six prosecution, acquitted in August 1976.",
  "sentence": "The federal court records a 1965 sentence of six months to ten years, imposed while Tate was already in custody. This entry dates that documented prison term at year precision; his earlier juvenile custody began before it. After the prison term ended, he remained in Marin County jail until his release on $100,000 bail on January 14, 1975. The August 1976 acquittal was not his release from custody.",
  "date_precision": "{\"incarceration_date\":\"year\",\"release_date\":\"day\"}"
}

https://clearinghouse-umich-production.s3.amazonaws.com/media/doc/2925.pdf
https://www.marxists.org/history/usa/pubs/black-panther/12%20no%2027%201-24%20jan%2025%201975.pdf

## David Johnson

Court p540 explicitly dates continuous prison custody August5,1968. Remove unsupportedAugust20,1971 arrest; retain 1993 release at existing year precision and sentencing date.

{
  "arrest_date": null,
  "incarceration_date": "1968-08-05 00:00:00",
  "charges": "Underlying second-degree burglary conviction; San Quentin Six prosecution - acquitted of murder, convicted of assault on correctional officers.",
  "sentence": "Six months to fifteen years for the underlying second-degree burglary conviction; the federal court records continuous imprisonment beginning August 5, 1968. The later San Quentin Six assault conviction was added to the prior state sentence. The existing 1993 release year is preserved; its exact month and day remain unresolved.",
  "date_precision": "{\"release_date\":\"year\",\"sentenced_date\":\"day\",\"incarceration_date\":\"day\"}"
}

https://clearinghouse-umich-production.s3.amazonaws.com/media/doc/2925.pdf

Court source and contemporary release scans reviewed. Full snapshots guarded; preserve biographies and unrelated fields. Applied live. Fixture, preview, global unrelated-field preservation, zero-update replay and all four public pages verified. Partial endpoints correctly do not produce a falsely exact counter. Separate follow-up: Spain profile birth/death identity fields appear inconsistent with contemporary reporting; not changed in this case-only batch.
