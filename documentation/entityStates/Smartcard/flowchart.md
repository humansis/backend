``` mermaid
flowchart LR
    NEW --> |new| UNASSIGNED
    UNASSIGNED --> |assigned| ACTIVE
    ACTIVE --> |Inactive for suspicion|INACTIVE
    ACTIVE --> |cancelled| CANCELLED
    INACTIVE --> |reactivate| ACTIVE
    INACTIVE --> |cancelled| CANCELLED
    INACTIVE --> |new beneficiary|REUSED
    REUSED --> |Copy the card to a new beneficiary| ACTIVE
