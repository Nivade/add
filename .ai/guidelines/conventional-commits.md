## Commit messages

This project follows [Conventional Commits](https://www.conventionalcommits.org). A husky `commit-msg` hook runs
commitlint with `@commitlint/config-conventional` and rejects anything else.

- Header: `type(optional-scope): subject`, at most 100 characters.
- Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`.
- Subject in the imperative, lower case start, no trailing period: `fix(auth): reject expired tokens`.
- Breaking change: `!` before the colon (`feat(api)!: drop v1 routes`) and a `BREAKING CHANGE:` footer.
- Blank line between header, body and footers; body and footer lines at most 100 characters.
- When the hook rejects a message, fix the message. Never bypass it with `--no-verify`.
