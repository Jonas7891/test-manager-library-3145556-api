# test-manager-library-3145556-api

> **Prueba técnica · Ficha ADSO 3145556**: backend de *Manager Library* en **PHP 8.4 + Laravel 13**, con arquitectura hexagonal.
> Spec: `test-manager-library-3145556-docs` (`spec-python/`, y la traducción a este stack en `spec-laravel/`).

## Arquitectura (artículos I a III)

```text
app/
├── Domain/          PHP puro: Book, Loan, LoanItem, Member, User, objetos de valor y errores de negocio
├── Application/     Ports/ (interfaces que la aplicación define) y Services/ (un caso de uso por clase)
├── Adapters/
│   ├── Inbound/Http/   controladores, middleware JWT y de rol, ErrorRenderer (cuerpo de error único), Presenter
│   └── Outbound/       Persistence/ (PostgreSQL), Security/ (Argon2id, JWT), Storage/ (portadas), Clock/
├── Bootstrap/       PortBindingsServiceProvider: ÚNICO punto de composición
└── Console/         library:bootstrap-admin (admin inicial desde el entorno)
database/migrations/ todo el DDL (ADR-001): esquema library, 6 tablas, CHECK, índices parciales y la semilla de categorías
```

No se usa Eloquent ni Sanctum: sus convenciones (`created_at`, ids autoincrementales, tabla de tokens) contradicen `data-model.md`.

## Cómo se corre

Con todo el sistema: ver `test-manager-library-3145556-infra` (`docker compose up -d --build`). La API queda en
`http://localhost:8000/api/v1`; al arrancar corre las migraciones y crea el admin desde `ADMIN_USERNAME` / `ADMIN_PASSWORD`.

## Cómo se prueba (evidencia)

```bash
docker compose -f docker-compose.test.yml run --rm --build tests
```

Levanta un PostgreSQL 16 efímero y corre las **59 pruebas** (387 aserciones):

| Suite | Qué prueba | Tareas |
|---|---|---|
| `tests/Unit/Domain` | Invariantes: ISBN, cantidades, 14 días, máximo 3, estado calculado, devolución doble | T-03…T-06 |
| `tests/Unit/Application` | Casos de uso contra fakes en memoria (`tests/Fakes`) | T-07…T-11 |
| `tests/Unit/Architecture` | El dominio no importa nada; la aplicación solo conoce el dominio; composición única | T-19 |
| `tests/Feature/*ContractTest` | Todas las rutas del contrato, códigos de error y cuerpo único contra PostgreSQL real | T-12, T-16, T-17 |
| `tests/Feature/ConcurrencyTest` | Último ejemplar: un préstamo gana y el otro recibe `BOOK_NOT_AVAILABLE` | T-13, RNF-2 |
| `tests/Feature/DatabaseInvariantsTest` | `CHECK` del motor, FK `RESTRICT`, ninguna columna con `DEFAULT` | T-02, T-18 |

## Mapa de historias a pruebas

| HU | Prueba principal |
|---|---|
| HU-01 Login | `AuthAndCatalogContractTest::test_login_hu_01`, `test_token_and_role_are_enforced_ca_01_3` |
| HU-02 Bibliotecarios | `test_create_librarian_hu_02` |
| HU-03 / HU-04 Libros | `test_books_crud_and_search_hu_03_hu_04`, `test_cover_upload_validates_type_and_size_ca_03_4` |
| HU-05 Socios | `test_members_hu_05` |
| HU-06 / HU-07 Préstamo y devolución | `LoansContractTest::test_lend_and_return_hu_06_hu_07`, `test_lending_rules_ca_06_2_ca_06_4` |
| HU-08 / HU-09 Listado y vencidos | `test_overdue_blocks_and_lists_ca_06_5_hu_09` |
| HU-10 Reporte | `test_report_is_frozen_and_admin_only_hu_10` |

## Configuración

Ver `.env.example`. Ningún secreto tiene valor por defecto ni se versiona (Art. IX). `JWT_SECRET` debe tener al menos 32 caracteres.
`CLOCK_OFFSET_DAYS` vale 0; solo se cambia para la fase de demostración del sembrador (ADR-006).

## Nota sobre portadas

PHP no interpreta `multipart/form-data` en un `PUT` real. Para `PUT /books/{id}/cover` el cliente envía `POST` con el campo
`_method=PUT` (suplantación de método de Laravel); la ruta del contrato se mantiene.
