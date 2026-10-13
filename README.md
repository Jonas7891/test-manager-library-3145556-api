# test-manager-library-3145556-api

> **Technical test · ADSO group 3145556**: *Manager Library* backend in **PHP 8.4 + Laravel 13**, with a hexagonal architecture.
> Spec: `test-manager-library-3145556-docs` (`spec-python/`, and its translation to this stack in `spec-laravel/`).

## Architecture (articles I to III)

```text
app/
├── Domain/          Plain PHP: Book, Loan, LoanItem, Member, User, value objects and business errors
├── Application/     Ports/ (interfaces the application defines) and Services/ (one use case per class)
├── Adapters/
│   ├── Inbound/Http/   controllers, JWT and role middleware, ErrorRenderer (single error body), Presenter
│   └── Outbound/       Persistence/ (PostgreSQL), Security/ (Argon2id, JWT), Storage/ (covers), Clock/
├── Bootstrap/       PortBindingsServiceProvider: the ONLY composition point
└── Console/         library:bootstrap-admin (initial admin from the environment)
database/migrations/ all the DDL (ADR-001): library schema, 6 tables, CHECKs, partial indexes and the category seed
```

Eloquent and Sanctum are not used: their conventions (`created_at`, auto-increment ids, a token table) contradict `data-model.md`.

API error messages (`message`) are in Spanish because users see them (ADR-015).

## How to run it

To run the whole system, see `test-manager-library-3145556-infra` (`docker compose up -d --build`). The API is served at
`http://localhost:8000/api/v1`. At startup it runs the migrations and creates the admin from `ADMIN_USERNAME` / `ADMIN_PASSWORD`.

## How to test it (evidence)

```bash
docker compose -f docker-compose.test.yml run --rm --build tests
```

It starts a throwaway PostgreSQL 16 and runs the **59 tests** (394 assertions):

| Suite | What it tests | Tasks |
|---|---|---|
| `tests/Unit/Domain` | Invariants: ISBN, quantities, 14 days, at most 3, computed status, double return | T-03…T-06 |
| `tests/Unit/Application` | Use cases against in-memory fakes (`tests/Fakes`) | T-07…T-11 |
| `tests/Unit/Architecture` | The domain imports nothing; the application only knows the domain; a single composition point | T-19 |
| `tests/Feature/*ContractTest` | Every contract route, error codes and the single body, against a real PostgreSQL | T-12, T-16, T-17 |
| `tests/Feature/ConcurrencyTest` | Last copy: one loan wins and the other gets `BOOK_NOT_AVAILABLE` | T-13, RNF-2 |
| `tests/Feature/DatabaseInvariantsTest` | Engine `CHECK`s, `RESTRICT` FKs, no column with `DEFAULT` | T-02, T-18 |

## Story-to-test map

| Story | Main test |
|---|---|
| HU-01 Login | `AuthAndCatalogContractTest::test_login_hu_01`, `test_token_and_role_are_enforced_ca_01_3` |
| HU-02 Librarians | `test_create_librarian_hu_02` |
| HU-03 / HU-04 Books | `test_books_crud_and_search_hu_03_hu_04`, `test_cover_upload_validates_type_and_size_ca_03_4` |
| HU-05 Members | `test_members_hu_05` |
| HU-06 / HU-07 Lending and return | `LoansContractTest::test_lend_and_return_hu_06_hu_07`, `test_lending_rules_ca_06_2_ca_06_4` |
| HU-08 / HU-09 List and overdue | `test_overdue_blocks_and_lists_ca_06_5_hu_09` |
| HU-10 Report | `test_report_is_frozen_and_admin_only_hu_10` |

## Configuration

See `.env.example`.
- No secret has a default value or is versioned (Art. IX).
- `JWT_SECRET` must be at least 32 characters long.
- `CLOCK_OFFSET_DAYS` is 0; it only changes for the seeder's demo phase (ADR-006).

## Note on covers

PHP does not parse `multipart/form-data` on a real `PUT`. For `PUT /books/{id}/cover`, the client sends `POST` with the field
`_method=PUT` (Laravel method spoofing), and the contract route stays the same (ADR-009).
