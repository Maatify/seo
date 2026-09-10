import re

with open('docs/audits/SEO_ARCHITECTURE_STANDARDS_CONTRACT_INTEGRITY_AUDIT.md', 'r') as f:
    content = f.read()

safe_target_pattern = r"(### Safe target\n\nIntroduce RFC-aware validation with explicit rules for:\n\n- valid product-token grammar\.\n- valid empty Allow/Disallow pattern\.\n- slash-prefixed path pattern\.\n- raw `#` comment semantics\.\n)- percent-encoded literal values where applicable\.\n(- CR/LF and other forbidden control characters in rule values\.\n- CR/LF safety for rule comments\.\n- CR/LF safety for top-level comments\.)"
safe_target_replacement = r"""\1- literal `#` in a path is represented using percent encoding such as `%23`.
\2"""

content = re.sub(safe_target_pattern, safe_target_replacement, content, flags=re.DOTALL)

with open('docs/audits/SEO_ARCHITECTURE_STANDARDS_CONTRACT_INTEGRITY_AUDIT.md', 'w') as f:
    f.write(content)
