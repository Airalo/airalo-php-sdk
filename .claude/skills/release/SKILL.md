---
name: release
description: Cut a new version of the Airalo PHP SDK by bumping SdkConstants::VERSION via PR, tagging, creating the GitHub release, and walking through the Packagist steps.
disable-model-invocation: true
argument-hint: "[patch|minor|major|X.Y.Z]"
---

# Release the SDK

This skill publishes a version that partners will install, so **every step with an external effect (push, PR, tag, GitHub release) needs the developer's explicit confirmation first.** Stop at any failed check.

Version facts:
- The version lives in `src/Constants/SdkConstants.php` as `VERSION`.
- Tags are plain semver **without** a `v` prefix (`2.0.1`, not `v2.0.1`).
- Packagist reads the tags from this GitHub repo.

## 1. Pre-checks

1. `git fetch --tags origin`, then confirm you're on `main`, it's up to date with `origin/main`, and the tree is clean.
2. Run `composer phpstan` and `composer phpunit`. Both must pass.
3. Show the last tag (`git describe --tags --abbrev=0`) and confirm it equals `SdkConstants::VERSION`. If they differ, stop and ask.

## 2. Choose the version

1. List the commits since the last tag: `git log <last-tag>..HEAD --oneline`.
2. Propose a bump. `$ARGUMENTS` overrides the proposal if given.
   - **major:** any breaking change to a public method signature or response shape.
   - **minor:** any `feat:` commit.
   - **patch:** only `fix:` / `chore:` commits.
3. Show the proposed version with the commit list and wait for confirmation.

## 3. Bump via PR

1. Ask for the ticket key for this release.
2. Create branch `chore/release-X.Y.Z`, set `VERSION = 'X.Y.Z'`, and commit `chore: release X.Y.Z (<TICKET>)`.
3. **Confirm**, then push and open the PR: `gh pr create --base main --title "chore: release X.Y.Z (<TICKET>)"`, with the commit list as the body.
4. Stop here and tell the developer to merge the PR once CI is green. Continue with step 4 after they say it's merged.

## 4. Tag and GitHub release

1. `git checkout main && git pull`, then confirm `SdkConstants::VERSION` is `X.Y.Z` on `main`.
2. **Confirm**, then:
   ```
   git tag X.Y.Z
   git push origin X.Y.Z
   gh release create X.Y.Z --title "X.Y.Z" --generate-notes --verify-tag
   ```
3. Show the release URL.

## 5. Packagist (manual)

Print this checklist for the developer. The agent can't do these steps.

- [ ] Open the `airalo/sdk` package page on packagist.org and click **Update**, unless the Packagist GitHub integration is enabled. With it enabled, the tag push updates Packagist automatically; this is a one-time setup a maintainer can do.
- [ ] Confirm `X.Y.Z` shows as the latest version.
- [ ] Optional: delete stale `dev-*` **branch** versions (e.g. `dev-feature/...`) for branches that have been merged or deleted.
- [ ] **Never delete released versions.** Partners may pin them, and Packagist re-adds any version whose tag still exists.

## 6. Wrap up

Summarise what was done (PR, tag, release URL) and what's left on the Packagist checklist.
