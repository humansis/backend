``` mermaid
stateDiagram-v2
    [*] --> UNASSIGNED : new
    UNASSIGNED --> ACTIVE : assigned
    ACTIVE --> INACTIVE : Inactive for suspicion
    ACTIVE --> CANCELLED : cancelled
    INACTIVE --> ACTIVE : reactivate
    INACTIVE --> CANCELLED : cancelled
    INACTIVE --> REUSED : new beneficiary
    REUSED --> ACTIVE : Copy the card to a new beneficiary
