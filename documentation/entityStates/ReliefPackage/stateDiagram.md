``` mermaid
stateDiagram-v2
    [*] --> TO_DISTRIBUTE
    TO_DISTRIBUTE --> DISTRIBUTION_IN_PROGRESS
    DISTRIBUTION_IN_PROGRESS --> DISTRIBUTED
    TO_DISTRIBUTE --> DISTRIBUTED
    TO_DISTRIBUTE --> EXPIRED
    TO_DISTRIBUTE --> CANCELLED
    EXPIRED --> TO_DISTRIBUTE
    EXPIRED --> DISTRIBUTED