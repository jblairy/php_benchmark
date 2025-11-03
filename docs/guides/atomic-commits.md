# Atomic Commits Guide

**Status**: Best Practice  
**Audience**: Developers, AI Coding Agents

## What is an Atomic Commit?

An **atomic commit** is a commit that:
- ✅ Contains **a single logical change**
- ✅ Is **complete** and **functional** by itself
- ✅ Can be **reverted** without breaking the project
- ✅ Has a **descriptive message** explaining the "why"

## Why It Matters

### 1. Readable Git History
```bash
# ❌ Bad - Catch-all commit
43ed2c3 fix: various changes

# ✅ Good - Atomic commits
43ed2c3 fix: use benchmark slug instead of class name for database benchmarks
0608e35 config: use DatabaseCodeExtractor for benchmark execution
09f85f7 feat: add DatabaseCodeExtractor for YAML benchmarks
```

### 2. Easier Debugging
```bash
# Find when a bug was introduced
git bisect start
git bisect bad HEAD
git bisect good v1.0.0

# With atomic commits: finds the exact responsible commit
# Without atomic commits: finds a large commit with 10 changes
```

### 3. Simplified Code Review
- Each commit can be reviewed independently
- Logical changes grouped together
- Easier to understand intent

### 4. Reversibility
```bash
# Revert a specific feature
git revert 43ed2c3  # Only reverts the slug fix

# With large commits: impossible to revert just one part
```

## Rules for Atomic Commits

### Rule 1: One Responsibility per Commit

**❌ Bad** - Multiple unrelated changes:
```bash
git commit -am "Add feature X, fix bug Y, update docs Z"
```

**✅ Good** - Separate commits:
```bash
git commit -m "feat: add feature X"
git commit -m "fix: resolve bug Y in component Z"
git commit -m "docs: update API documentation"
```

### Rule 2: Complete and Functional Commit

**❌ Bad** - Broken code:
```bash
# Commit 1: Add new method (but not used anywhere)
# Commit 2: Call the method (but method signature wrong)
# Commit 3: Fix method signature
# → Commits 1 and 2 break the build
```

**✅ Good** - Each commit compiles:
```bash
# Commit 1: Add and integrate new method (complete feature)
# Commit 2: Refactor method for better performance
# → Each commit is functional
```

### Rule 3: Descriptive Message

**❌ Bad** - Vague message:
```bash
git commit -m "fix stuff"
git commit -m "update"
git commit -m "changes"
```

**✅ Good** - Explanatory message:
```bash
git commit -m "fix: use benchmark slug instead of class name for database benchmarks

Problem: DatabaseBenchmark::class returns same value for all YAML benchmarks
Solution: Use slug (e.g., 'iterate-with-for') instead of class name
Impact: Results now correctly identifiable in database"
```

## Practical Example: Fix YAML Benchmarks

### Context
After migration to YAML fixtures, benchmarks no longer execute. Multiple issues to resolve.

### ❌ Monolithic Approach (1 large commit)
```bash
git add .
git commit -m "fix: YAML benchmarks"

# Contains:
# - New DatabaseCodeExtractor.php
# - Modified services.yaml
# - Modified DoctrinePulseResultPersister.php
# - Documentation FIX_YAML_BENCHMARKS.md
```

**Problems**:
- ❌ Impossible to understand each change
- ❌ If one change is bad, must revert everything
- ❌ Difficult to do code review
- ❌ Uninformative git history

### ✅ Atomic Approach (4 separate commits)

```bash
# Commit 1: Create the solution
git add src/Infrastructure/Execution/CodeExtraction/DatabaseCodeExtractor.php
git commit -m "feat: add DatabaseCodeExtractor for YAML benchmarks"

# Commit 2: Configure usage
git add config/services.yaml
git commit -m "config: use DatabaseCodeExtractor for benchmark execution"

# Commit 3: Fix persister bug
git add src/Infrastructure/Persistence/Doctrine/DoctrinePulseResultPersister.php
git commit -m "fix: use benchmark slug instead of class name for database benchmarks"

# Commit 4: Document changes
git add docs/guides/fix-yaml-benchmarks.md
git commit -m "docs: document YAML benchmark execution fix"
```

**Benefits**:
- ✅ Each commit has a clear purpose
- ✅ Can revert a specific commit if needed
- ✅ Easy to understand evolution
- ✅ Code review commit by commit
- ✅ Git history tells a story

## Good Commit Message Structure

### Recommended Format (Conventional Commits)

```
<type>: <short description>

<message body>
<explanation of why>
<context if necessary>

<optional footer>
```

### Commit Types

| Type | Usage | Example |
|------|-------|---------|
| `feat` | New feature | `feat: add DatabaseCodeExtractor` |
| `fix` | Bug fix | `fix: use benchmark slug instead of class name` |
| `refactor` | Refactoring (no functional change) | `refactor: extract method for clarity` |
| `docs` | Documentation only | `docs: add atomic commits guide` |
| `test` | Add/modify tests | `test: add unit tests for CodeExtractor` |
| `config` | Configuration | `config: use DatabaseCodeExtractor` |
| `chore` | Maintenance tasks | `chore: update dependencies` |
| `perf` | Performance improvement | `perf: optimize database queries` |

### Complete Example

```
fix: use benchmark slug instead of class name for database benchmarks

DoctrinePulseResultPersister now uses benchmark slug for DatabaseBenchmark
instances instead of class name.

Problem:
- $benchmark::class returns 'DatabaseBenchmark' for all YAML benchmarks
- Results were saved with wrong identifier
- Impossible to distinguish between benchmarks

Solution:
- Check if benchmark is DatabaseBenchmark instance
- Use slug (e.g., 'iterate-with-for') for YAML benchmarks
- Use class name for legacy PHP class benchmarks

Impact:
- Results now correctly identifiable in database
- Backward compatibility maintained for PHP class benchmarks

Before: name='DatabaseBenchmark', bench_id='DatabaseBenchmark' ❌
After: name='iterate-with-for', bench_id='iterate-with-for' ✅
```

## Atomic Commits Workflow

### 1. Plan Commits

**Before coding**:
```
Task: Fix YAML benchmark execution

Planned commits:
1. Create DatabaseCodeExtractor
2. Configure services.yaml
3. Fix DoctrinePulseResultPersister
4. Add documentation
```

### 2. Code and Commit Progressively

```bash
# ❌ Don't code everything then commit
# Code 4h → git add . → git commit

# ✅ Commit as you go
# Code 30min → git add file1 → git commit
# Code 30min → git add file2 → git commit
# etc.
```

### 3. Use Selective git add

```bash
# Add file by file
git add src/Infrastructure/Execution/CodeExtraction/DatabaseCodeExtractor.php
git commit -m "feat: add DatabaseCodeExtractor"

# Or add by patch (-p) to select parts
git add -p config/services.yaml
git commit -m "config: update CodeExtractor"
```

### 4. Verify Before Committing

```bash
# See what will be committed
git diff --staged

# Verify code compiles
make quality
make phpstan

# Then commit
git commit -m "..."
```

## Anti-Patterns to Avoid

### ❌ Anti-Pattern 1: WIP Commits

```bash
git commit -m "WIP"
git commit -m "WIP2"
git commit -m "final version"
git commit -m "final version for real"
```

**Solution**: Use `git commit --amend` or `git rebase -i` to clean up.

### ❌ Anti-Pattern 2: Catch-All Commits

```bash
git commit -am "update everything"
```

**Solution**: `git add` file by file with separate commits.

### ❌ Anti-Pattern 3: Broken Commits

```bash
# Commit 1: Add method (but incomplete)
# Commit 2: Fix compilation error
```

**Solution**: Wait for code to be complete before committing.

### ❌ Anti-Pattern 4: Vague Messages

```bash
git commit -m "fix"
git commit -m "changes"
git commit -m "update code"
```

**Solution**: Explain **what**, **why** and **how**.

## For AI Coding Agents

### Directives for Claude/GPT/Agents

1. **Always** analyze changes before committing
2. **Group** modifications by logical responsibility
3. **Create** one commit per responsibility
4. **Write** descriptive messages with context
5. **Verify** each commit is functional
6. **Document** important decisions

### Workflow Template

```bash
# Step 1: Analyze changes
git status
git diff

# Step 2: Identify logical groups
# Group 1: New DatabaseCodeExtractor file
# Group 2: services.yaml configuration
# Group 3: Persister fix

# Step 3: Commit atomically
git add group_1_file
git commit -m "type: description group 1"

git add group_2_file
git commit -m "type: description group 2"

git add group_3_file
git commit -m "type: description group 3"

# Step 4: Verify history
git log --oneline -5
```

## Useful Tools

### View History

```bash
# Compact history
git log --oneline -20

# History with modified files
git log --stat -10

# Graphical history
git log --graph --oneline --all -20

# Search for a commit
git log --grep="DatabaseCodeExtractor"
```

### Modify History (Before Push)

```bash
# Modify last commit
git commit --amend

# Modify multiple commits
git rebase -i HEAD~3

# Squash multiple commits into one
git rebase -i HEAD~3
# Then mark commits to squash with 's'
```

### Undo Changes

```bash
# Undo last commit (keep changes)
git reset --soft HEAD~1

# Undo a specific commit (creates new commit)
git revert abc1234

# Remove file from staging
git restore --staged file.php
```

## Pre-Commit Checklist

Before each commit, verify:

- [ ] Code compiles without errors
- [ ] Tests pass
- [ ] Commit contains only one responsibility
- [ ] Message explains the "why"
- [ ] No unrelated files (debug, temp, etc.)
- [ ] Changes are complete and functional
- [ ] Code respects project standards

## Real Examples from this Project

### Good History (Atomic Commits)

```bash
43ed2c3 fix: use benchmark slug instead of class name for database benchmarks
0608e35 config: use DatabaseCodeExtractor for benchmark execution
09f85f7 feat: add DatabaseCodeExtractor for YAML benchmarks
a51de34 docs: refactor and harmonize documentation (fixtures + naming)
c0efef0 feat: add YAML benchmark fixtures (100+ benchmarks)
```

**Why it's good**:
- Each commit has clear responsibility
- Descriptive messages
- Logical order (implementation → configuration → documentation)
- Easy to understand evolution

### Bad History (Before Refactoring)

```bash
71a8530 chore: clean Dashboard dependencies
24e3ec6 chore: clean Dashboard dependencies
18a6e6a chore: clean Dashboard dependencies
e2db171 chore: clean Dashboard dependencies
...
```

**Why it's bad**:
- 22 commits with same message
- Impossible to understand what was done
- Impossible to revert a specific part
- Useless history

## Conclusion

Atomic commits are an **essential practice** for:
- 📖 **Readable** Git history
- 🐛 Easier **debugging**
- 🔍 Efficient **code reviews**
- ↩️ Precise **reversibility**
- 🤝 Better **collaboration**

**Golden Rule**: Each commit should tell **one simple and complete story**.

---

**See also**:
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Git Best Practices](https://git-scm.com/book/en/v2/Distributed-Git-Contributing-to-a-Project)
- [CLAUDE.md](../../CLAUDE.md) - Project standards
