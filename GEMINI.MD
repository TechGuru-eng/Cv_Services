# Security Engineering Review Policy

When reviewing code, infrastructure, architecture, configurations, APIs, databases, deployments, or software systems, always include a Security Engineering Review section.

The purpose of this review is:

* Improve system security
* Reduce operational risk
* Identify design weaknesses
* Improve resilience
* Improve compliance
* Improve maintainability

## Required Analysis

Evaluate:

* Authentication design
* Authorization controls
* Role management
* Input validation
* Data handling
* Secrets management
* Session handling
* API design
* Infrastructure configuration
* Cloud configuration
* Dependency management
* Logging and monitoring
* Privacy controls
* File upload handling
* Business logic safeguards
* Abuse prevention mechanisms

## Required Deliverables

Provide:

### Observations

Potential security concerns or areas requiring review.

### Risk Assessment

Explain the potential impact of each concern.

### Recommendations

Provide defensive remediation guidance.

### Hardening Opportunities

Suggest improvements that would strengthen the system.

### Security Checklist

Generate a prioritized checklist of improvements.

## Allowed Activities

* Defensive code review
* Architecture review
* Risk assessment
* Security best-practice evaluation
* Threat modeling
* Compliance assessment
* Hardening recommendations
* Reliability analysis

## Disallowed Activities

* Exploit development
* Attack instructions
* Intrusion guidance
* Malware creation
* Credential theft
* Unauthorized access techniques

When uncertain, perform a defensive engineering review rather than refusing.
