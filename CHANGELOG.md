# Changelog
## [4.4]

### Added

- `<x-commentify::thread>` keys the Livewire thread to the record and thread name, so a Filament modal shows the record you opened and two threads on one record stay separate. `CommentsEntry::make()` drops that thread on an edit form or relation manager form.
- With notifications enabled, and Pro not installed, a reply notifies the parent author. A top-level comment notifies the commentable's user when that relation exists. The queued notification waits until the database transaction commits. The mail button is included only when the comment URL is on the application host.

### Changed

- Minimum PHP is 8.3.

## [4.3]

### Added

- Optional lazy loading for the comments component. `commentify.lazy` defaults to `false`, so the thread stays in the first HTML response. A tag can pass `lazy` or `:lazy="false"`. Tailwind and Bootstrap placeholder views and a Filament toggle ship with the flag.
- Optional recursive CTE thread loader. `commentify.tree_loader` defaults to `eager`, and `commentify.max_depth` defaults to `2` (clamped from 0 to 50). A depth of `0` does not preload replies.
- `CommentPosting`, dispatched after the author is associated and before the comment is saved. A listener can cancel the save.
- `CommentUnliked` for unlike actions.
- `MentionSearch` for the default @mention query. `%`, `_`, and `\` in the term are escaped.
- `Comment::forThread()` and `Comment::hasThreadColumn()`. The scope does nothing when the `thread` column is absent.
- `HasLikes::viewerLikeExists()` so a list can mark the current viewer's like without loading every like row.
- `Comment::isEdited()`, which is false unless `commentify_comment_revisions` exists and contains a row for the comment.
- Livewire hooks another package can override: the thread on `mount`, `resolvedThread`, `commentSetting`, read-only and approval checks, the rate-limit maximum, extra validation, mass-assignment attributes, `prepareCommentForCreate`, `beforeCommentCreate`, `orderChildren`, and `resetComposer`.
- Config keys those hooks read: `honeypot`, `rate_limit`, `mentions`, `schema_org`, and `replies_per_page`.
- English and Spanish strings for the loading placeholder, the edited marker, the deleted placeholder, and the honeypot label.
- Slipway as a dev dependency, with a generated GitHub Actions workflow. It validates Composer, audits dependencies, scans for secrets, and runs PHPUnit on PHP 8.3 and 8.4.
- A description on each method in `src`.

### Changed

- `postComment()` stops when `CommentPosting` is cancelled or when `save()` returns false, then clears the composer.

## [4.2] - 2026-09-16

### Added

- A configurable login route for the guest "log in to comment" link.
- `IntegerAuthId`, so likes and reports keep working when the authenticated id is a numeric string.
- A check that `commentify.user_model` is an Eloquent model.

### Fixed

- Guest name and email fields follow `guestCommentsAllowed()`.
- The sort dropdown no longer sits underneath the composer.
- The profile URL key used for mention links.
- CSS framework selection for the published views.

## [4.1] - 2026-09-10

### Fixed

- Guest posting.

## [4.0] - 2026-09-09

### Added

- Guest comments: name, email, avatars, policy, and import columns.
- English and Spanish translations.
- Pinned comments sort first when `pinned_at` exists.
- Comment URLs for notifications.
- A security policy for private vulnerability reports.

## [3.9] - 2026-09-01

### Added

- Markdown composer, with preview and tests.

## [3.8] - 2026-07-07

### Added

- Laravel 13 support.

## [3.7] - 2026-02-19

### Added

- `commentify.user_model` configuration.

## Older tags

Tags `1.0` through `3.6` are on GitHub.
