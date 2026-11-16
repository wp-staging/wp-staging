# Repository Guidelines
Rules for every agent working in this repository: Claude Code, Codex, the Copilot coding agent and
local models. `CLAUDE.md` imports this file and adds only what is specific to Claude Code, so a rule
that binds every agent is written here and nowhere else.

## What this is

The released **WP STAGING Free** plugin — the tree that ships to wordpress.org. Day-to-day plugin
development happens in **`wp-staging-pro`**, which builds both editions and carries the architecture,
the tests, the Make and the workflow skills. Come here for a release merge and for reading what
actually shipped; not for feature work.

## Working rules

- Feature and fix work goes into `wp-staging-pro` on a branch with an issue, following that
  repository's `AGENTS.md` and its skills. A change copied the other way — Free here first — leaves
  the Pro tree behind and gets reverted by the next build.
- A commit in this repository is a release artifact. Its message says which release it belongs to.
- The Free edition may preview a Pro capability but never implements it: no handler, no endpoint, no
  markup that only needs a condition flipped. `wp-staging-pro` § Development Guidelines item 9 owns
  that rule.
