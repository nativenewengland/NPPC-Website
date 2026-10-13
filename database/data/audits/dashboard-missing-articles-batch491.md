# Batch 491: researched missing dashboard articles

Inventory reviewed: 1,216 curated links, including the newly deployed Omar report. Before this addition, October had three entries. Twenty distinct missing reports were selected against existing URLs, AP article IDs, headline subjects and event dates. A July 1 Willamette Week review was excluded because it is already present.

Coverage: protest arrests and demonstrations, detention resistance, court updates, enforcement accountability, and press freedom. Existing stories about McIver, Martinez, Centennial protesters and newsroom restrictions are preserved; the additions cover later, distinct developments. The payload contains each source, date basis and qualifications. Source publications were read directly or through the publisher-indexed report; AP hearing location was corroborated in AP photo captions. Only brief original paraphrases are stored, not article bodies.

Dates use an explicitly reported event day when available, otherwise the report publication day. Midnight is a storage convention for day precision and does not assert an exact event time. Meriden text and two captions say October 1; one image caption differs. The narrative date is used. Folkston exact strike-start day is unspecified, so the publication date is used. Centennial reports a planned state-charge dismissal, while federal charges remained pending. Whistleblower and lawsuit allegations remain attributed.

Eighteen entries use city/locality markers; the two national reports remain newswire-only. Coordinates do not claim exact incident sites or residential addresses. No prisoners, biographies, institutions or cases are changed.

Replay matches normalized host/path, strips tracking parameters, and recognizes alternate AP URL slugs by article ID. All entries are preflighted within one transaction; conflicting existing rows abort instead of being replaced. The runner keeps the existing API cache invalidation convention and PsySH writable-path workaround.

Deployment, idempotence and public newswire/map verification will be recorded in batch491-live-verification.json.
