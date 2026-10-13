# AGENTS.md: Airalo PHP SDK

Instructions for AI coding agents working in this repository. Human contributors are welcome to read it too.

## Where changes happen

This public repository (`github.com/Airalo/airalo-php-sdk`) is the canonical source for the SDK.
All SDK changes originate here, as pull requests against `main`.

## What this is

`airalo/sdk` is the PHP client for the Airalo Partner API (eSIM packages, orders, top-ups, SIM usage, vouchers, exchange rates, future orders, compatible devices).
It handles authentication, request signing, encryption, caching and rate-limit handling for the partner.

- PHP `>=8.0` runtime support; CI runs on PHP 8.3. Requires the `curl` and `sodium` extensions.
- Single runtime dependency: `psr/simple-cache`. Keep it that way.
- Distributed on Packagist; public method signatures are a semver contract.

### Public surface

- `Airalo\Airalo` (`src/Airalo.php`): instance API, `new Airalo($config, ?CacheInterface $cache)`.
- `Airalo\AiraloStatic` (`src/AiraloStatic.php`): static API, `AiraloStatic::init($config, ?CacheInterface $cache)`.
  Both facades expose the **same** methods and must stay in sync.
- Responses are `?Airalo\Helpers\EasyAccess`, which supports both array and object access.
- Every error the SDK throws is `Airalo\Exceptions\AiraloException`.
- `mock()` on both facades returns `tests/Mock/AiraloMock.php` so partners can stub calls in their tests.
- `alo.php` is a custom autoloader for projects that don't use Composer.

## Layout

```
src/
├── Airalo.php, AiraloStatic.php   # Facades; Airalo::initServices() wires services
├── Config.php                     # Config parsing + validation (client_id, client_secret mandatory)
├── Constants/                     # ApiConstants (endpoints), SdkConstants
├── Services/                      # One *Service per API area (Packages, Order, Topup, Sim, …)
├── Resources/                     # CurlResource, MultiCurlResource (HTTP)
├── Helpers/                       # EasyAccess, CacheTrait, FilesystemCache, Signature, Crypt, …
└── Exceptions/AiraloException.php
tests/                             # Mirrors src/ (Services/, HelperTests/, Mock/)
README.md                          # Public method reference ("Methods Interface")
```

### Adding or changing an endpoint

1. Add the endpoint path to `src/Constants/ApiConstants.php`.
2. Implement the call in the relevant `src/Services/*Service.php`, or add a new service. Validate inputs and throw `AiraloException` before any HTTP call.
3. Wire any new service in **both** `Airalo::initServices()` and `AiraloStatic::initServices()`, adding the matching property to each class.
4. Add the public method to **both** `Airalo` and `AiraloStatic`, with matching signatures and PHPDoc.
5. Update `tests/Mock/AiraloMock.php` if the change affects it.
6. Document the method in the `README.md` "Methods Interface" section.
7. Add or extend tests.

## Code conventions

- PSR-12 style as used in the existing code: 4 spaces, braces on their own line for classes and methods.
- Typed properties, parameters and return types. PHPDoc `@param` and `@return` with array shapes, so PHPStan level 9 can reason about them.
- Stay PHP 8.0 compatible: no enums, `readonly`, `never`, first-class callable syntax or other 8.1+ features.
- Caching goes through `CacheTrait::cacheRemember()` and the injected PSR-16 cache. Don't write to the filesystem directly.
- Don't add `@phpstan-ignore` annotations without a comment explaining why.
- Match the surrounding code; don't reformat files outside the task.

## Testing

- PHPUnit 9.6, configured in `phpunit.xml`. Tests live under `tests/` and mirror `src/`.
- Unit tests only. **Never call the real Partner API from tests.** Mock `Config`, `CurlResource`, `MultiCurlResource` and `Psr\SimpleCache\CacheInterface` (see `tests/Services/SimServiceTest.php`).
- Use `@dataProvider` for validation edge cases.
- Every behaviour change needs a test that fails without it.

## Quality gates

Run these in order after `composer install`. CI (`.github/workflows/`) runs the same two checks on every PR to `main`.
`composer.lock` is not committed, so a local lock can go stale. If tests fail with a missing class or interface (for example `Psr\SimpleCache\CacheInterface`), run `composer update` first.

1. `composer phpstan`: PHPStan level 9 on `src/`.
2. `composer phpunit`: full test suite.

If a gate fails, make the smallest fix that makes it pass, rerun the same gate, and don't move on until it passes.
Don't use `composer phpstan-update-baseline` to hide new errors; only regenerate the baseline with explicit approval.

## Definition of done

- Both gates pass.
- Both facades, the service, the README and the tests are updated for any public-surface change.
- No breaking change to a public signature without explicit approval (this would need a major version bump).
- No credentials, tokens or customer data in code, tests, fixtures or docs.

## Branches, commits and PRs

- Branch from `main`, named `<type>/<TICKET>-short-description`, where `<type>` is `feature`, `fix` or `bugfix` (e.g. `feature/AP-1234-sim-usage-bulk`).
- Commits and PR titles use Conventional Commits with the ticket key in parentheses:
  `feat: Support injecting custom cache instance (AP-8767)`, `fix: ...`, `chore: ...`.
- PRs target `main`, need green CI, and are squash-merged.

## Off limits without explicit approval

- New Composer dependencies, or raising the minimum PHP version.
- `phpstan-baseline.neon`, `phpstan-ignore-errors.neon`, `.github/workflows/`.
- Breaking changes to public method signatures or response shapes.
- Weakening auth, request signing (`Helpers/Signature.php`), encryption (`Helpers/Crypt.php`) or TLS verification in `Resources/` (`CurlResource::ignoreSSL()` must stay opt-in).

## AI tooling

Claude Code picks these up automatically. Other agents can read them as plain workflow docs.

- `.claude/skills/add-endpoint/`: step-by-step workflow for adding or changing an endpoint.
- `.claude/skills/release/`: release workflow (`/release`): version bump PR, tag, GitHub release, Packagist checklist.
- `.claude/agents/sdk-reviewer.md`: SDK-specific diff review; run it before opening a PR.
- `.claude/hooks/php-lint.sh`: runs `php -l` on every PHP file Claude edits (wired in `.claude/settings.json`).

## Security

- This is a public repository. Never commit `client_id`, `client_secret`, access tokens or partner account identifiers, not even sandbox ones. Use placeholders like `<YOUR_API_CLIENT_ID>` in docs and examples.
- Never log or echo credentials, tokens, ICCIDs or other customer data.
- Keep local experiment scripts untracked.
