# Exact duration display fix

Applied live on 2026-10-18. Shared formatter no longer displays estimated day bounds. Supported month precision uses calendar-month differences. Unsupported partial totals are suppressed rather than presenting an exact subset as the total. No database records changed.

Validation: three PHP syntax checks, 40 regression assertions, all 10,850 rebuilt API profiles have zero estimated counters. Public API confirms Jeff Keith: 1 year 1 month; Herbert Mahler: 4 years 2 months; Robert Swann: 2 years; Marjorie Swann: 5 months 1 day. Sakay no longer displays an unsupported duration. Public profile month counters verified.

Live API and Blade had unrelated pre-existing edits. The included patch records only this deployment relative to those live files; manifest records guarded before/after SHA256 values. All unrelated live edits preserved. API and compiled view caches refreshed.
