# Batch 334: Buffalo political-exclusion detainees

Four missing people, four US custody periods, one portrait, two birthdates and one death date. Accumulation reaches **40/100 new people**, 54 periods, 12 photos, four birth dates and four death dates. Local preparation only; no production writes or new PR.

| Person | Added information |
|---|---|
| Edward (Ted) Howell | February 6, 1982 entry; year-only US endpoint; May 20, 1947 birth; January 3, 2025 death; individually identified obituary portrait. |
| William Gilroy | February 6, 1982 entry; February bail return; February 27, 1945 birth. |
| William O’Neill / O’Neil | February 6, 1982 entry; February bail return. |
| James Kelly | February 6, 1982 entry; February bail return; distinct from existing James Whiteford. |

The main habeas opinion documents sustained confinement despite criminal bail. Its supplement ends INS custody on February 19; it does not establish physical release from every US custodian. Therefore no exact release day is entered. The labour record confirms the three Canadian residents returned on bail during February. Howell later appears in Canadian detention, so his US endpoint remains year precision. Canadian, British and Irish imprisonment is excluded. The three Canadian residents received time-served dispositions on March 28, 1983; sentencing is not used as their release date. UPI's February16 arrest wording conflicts with the court and indictment's February6 and is not used. The court says Rainbow Bridge while the indictment and wire say Whirlpool; no precise bridge coordinates are assigned.

Two detailed obituaries independently state Howell's May20,1947 birth. Earlier funeral coverage says78, and some1982 reporting says35; those ages are not used to calculate a competing birthday. The funeral-director notice establishes death on January3 rather than the January7 funeral. Gilroy's birthday is explicit in a contemporaneous employment memorandum reproduced in the labour decision. Its personal contact and identification numbers are not copied. The other two birthdays and all remaining death dates remain unresolved. No support-website field is populated from citations.

## Namesake distinction

The live preview initially matched James Kelly to **James Whiteford**, ID `ccbf538e-c9be-4f22-997f-7025ac1e5d20`, whose alias is Kelly. Whiteford was a New York-born cook aged36 jailed after the1916 Everett Massacre. The Buffalo defendant was a Canadian resident reported as42 in1982. These are different people. The runner excludes only that reviewed ID, only for this new James Kelly, and only while its six identity fields match the inspected record. Any change stops the batch. All other duplicate and ambiguity checks remain. No Whiteford field or case is changed.

## Validation

202 assertions passed with writes confined to SQLite memory and mocked file/storage/cache operations. Tests cover insertion, replay, partial dates, aliases, existing-record preservation, namesake preservation and refusal when that namesake identity changes. Forced query-only live preview finds all four absent after the reviewed distinction. Normalized identity checks against8957-profile snapshot and pending297–333 found no full-name collision. Shell syntax and photo checksum pass.

## Queue87 handoff

Accessible-source pass now covers batches331–334:16 new people and19 US episodes, two portraits, three birthdates and two death dates. This is not an exhaustive IRA inventory. Retain the held leads in audits331–333: individual Tucson custody for John Lynch/William Kelly; Freedom Five service dates; Noel Gaynor US duration; unresolved asylum-only leads; existing Kevin Art/Artt and Mackin component-field mismatch. The companion Buffalo petition is now resolved. Continue other affiliations rather than repeatedly treating unverified leads as additions.

## Sources

- [Gilroy v. Ferro, 534 F.Supp.321 (W.D.N.Y., February 19, 1982). Actual February 6 detention and continued custody despite criminal bail; political-exclusion allegations.](https://law.justia.com/cases/federal/district-courts/FSupp/534/321/1443784/)
- [Gilroy v. Ferro, 534 F.Supp.326 (W.D.N.Y., February 19, 1982). Supplement records end of respondent INS custody; not conclusive evidence of physical release from all US custody.](https://law.justia.com/cases/federal/district-courts/FSupp/534/326/1443585/)
- [Electrical Power Systems Construction Council and IBEW Local 1788 v. Ontario Hydro, 1983 OLRB mini 12, paragraphs 13-14 and reproduced indictment. Employment memo, birth date, bail reporting and Canadian imprisonment distinction.](https://www.minicounsel.ca/olrb/1983/m12)
- [UPI, March 29, 1983, Three St Catharines residents sentenced. March 28 time-served dispositions; wire arrest-date error not used.](https://www.upi.com/amp/Archives/1983/03/29/Three-St-Catharines-Ont-residents-have-been-sentenced-to/9487417762000/)
- [Irish Times, January 18, 2025, Ted Howell obituary. Individually identified portrait credited to ONeills Funeral Directors Belfast/Facebook; explicit May 20, 1947 birth and January 3, 2025 death; 1982 case identity.](https://www.irishtimes.com/obituaries/2025/01/18/ted-howell-obituary-republican-figure-whose-influence-was-far-greater-than-his-profile/)
- [Deaglan De Breadun, Irish Independent, January 19, 2025, Ted Howell obituary. Independently gives May 20, 1947 birth.](https://www.independent.ie/irish-news/obituary-ted-howell-republican-who-helped-to-guide-sinn-fein-through-the-peace-process-negotiations/a1981664997.html)
- [ONeills Funeral Directors notice for Ted Howell, Funeral Times. January 3, 2025 death explicitly distinguished from January 7 funeral.](https://www.funeraltimes.com/tedhowell341857564)
