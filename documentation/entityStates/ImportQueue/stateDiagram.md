``` mermaid
stateDiagram-v2
    %% integrity
    [*] --> NEW
    NEW --> VALID
    NEW --> INVALID
    INVALID --> INVALID_EXPORTED

    %% identity
    VALID --> IDENTITY_CANDIDATE
    VALID --> UNIQUE_CANDIDATE
    
    %% similarity
    UNIQUE_CANDIDATE --> SIMILARITY_CANDIDATE

    %% duplicity resolving
    UNIQUE_CANDIDATE --> TO_CREATE
    TO_CREATE --> TO_CREATE
    TO_UPDATE --> TO_CREATE
    TO_LINK --> TO_CREATE
    TO_IGNORE --> TO_CREATE
    IDENTITY_CANDIDATE --> TO_UPDATE
    SIMILARITY_CANDIDATE --> TO_UPDATE
    TO_CREATE --> TO_UPDATE
    TO_UPDATE --> TO_UPDATE
    TO_LINK --> TO_UPDATE
    TO_IGNORE --> TO_UPDATE

    %% to_ignore is reserved for duplicities between queue
    %% and queue, and it means it shouldn't be imported

    IDENTITY_CANDIDATE --> TO_IGNORE
    SIMILARITY_CANDIDATE --> TO_IGNORE
    TO_CREATE --> TO_IGNORE
    TO_UPDATE --> TO_IGNORE
    TO_LINK --> TO_IGNORE
    TO_IGNORE --> TO_IGNORE
    IDENTITY_CANDIDATE --> TO_LINK
    SIMILARITY_CANDIDATE --> TO_LINK
    TO_CREATE --> TO_LINK
    TO_UPDATE --> TO_LINK
    TO_LINK --> TO_LINK
    TO_IGNORE --> TO_LINK

    %% reset
    IDENTITY_CANDIDATE --> VALID
    SIMILARITY_CANDIDATE --> VALID
    UNIQUE_CANDIDATE --> VALID
    TO_CREATE --> VALID
    TO_UPDATE --> VALID
    TO_LINK --> VALID
    TO_IGNORE --> VALID

    %% finish
    TO_CREATE --> CREATED
    TO_UPDATE --> UPDATED
    TO_IGNORE --> LINKED
    TO_LINK --> LINKED
    TO_IGNORE --> IGNORED

    %% errors, from ALL states where is anything happening
    NEW --> ERROR
    VALID --> ERROR
    INVALID --> ERROR
    IDENTITY_CANDIDATE --> ERROR
    UNIQUE_CANDIDATE --> ERROR
    SIMILARITY_CANDIDATE --> ERROR
    TO_CREATE --> ERROR
    TO_UPDATE --> ERROR
    TO_LINK --> ERROR
    TO_IGNORE --> ERROR
