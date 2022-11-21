``` mermaid
flowchart LR

    subgraph integrity
        NEW --> |VALIDATE| VALID
        NEW --> |INVALIDATE| INVALID
        INVALID --> |INVALIDATE_EXPORT| INVALID_EXPORTED
    end

    subgraph identity
        VALID --> |IDENTITY_CANDIDATE| IDENTITY_CANDIDATE
        VALID --> |UNIQUE_CANDIDATE| UNIQUE_CANDIDATE
    end

    subgraph similarity
        UNIQUE_CANDIDATE --> |SIMILARITY_CANDIDATE| SIMILARITY_CANDIDATE
    end

    subgraph CREATE
        TO_CREATE --> |TO_CREATE| TO_CREATE
    end

    subgraph UPDATE
        TO_UPDATE --> |TO_UPDATE| TO_UPDATE
    end

        subgraph IGNORE
        TO_IGNORE --> |TO_IGNORE| TO_IGNORE
    end

    subgraph LINK
        TO_LINK --> |TO_LINK| TO_LINK
    end
    
    subgraph duplicity resolving
        UNIQUE_CANDIDATE --> |TO_CREATE| CREATE
        TO_UPDATE --> |TO_CREATE| CREATE
        TO_LINK --> |TO_CREATE| CREATE
        TO_IGNORE --> |TO_CREATE| CREATE
        IDENTITY_CANDIDATE --> |TO_UPDATE|UPDATE
        SIMILARITY_CANDIDATE --> |TO_UPDATE| UPDATE
        TO_CREATE --> |TO_UPDATE| UPDATE
        TO_LINK --> |TO_UPDATE| UPDATE
        TO_IGNORE --> |TO_UPDATE| UPDATE
    end
    subgraph to ignore is reserved  for duplicities
        IDENTITY_CANDIDATE --> |TO_IGNORE| IGNORE
        SIMILARITY_CANDIDATE --> |TO_IGNORE| IGNORE
        TO_CREATE --> |TO_IGNORE| IGNORE
        TO_UPDATE --> |TO_IGNORE| IGNORE
        TO_LINK --> |TO_IGNORE| IGNORE
        IDENTITY_CANDIDATE --> |TO_LINK| LINK
        SIMILARITY_CANDIDATE --> |TO_LINK| LINK
        TO_CREATE --> |TO_LINK| LINK
        TO_UPDATE --> |TO_LINK| LINK
        TO_IGNORE --> |TO_LINK| LINK
    end

    re{RESET}
    IDENTITY_CANDIDATE --> |RESET| re
    SIMILARITY_CANDIDATE --> |RESET| re
    UNIQUE_CANDIDATE --> |RESET| re
    TO_CREATE --> |RESET| re
    TO_UPDATE --> |RESET| re
    TO_IGNORE --> |RESET| re
    TO_LINK --> |RESET| re
    
    re ==> |RESET| VALID

    subgraph finish 
        TO_CREATE --> |CREATE| CREATED
        TO_UPDATE --> |UPDATE| UPDATED
        TO_IGNORE --> |LINK| LINKED
        TO_LINK --> |LINK| LINKED
        TO_IGNORE --> |IGNORE| IGNORED
    end

    subgraph errors
        NEW  --> |FAIL_UNEXPECTED| ERROR
        VALID --> |FAIL_UNEXPECTED| ERROR
        INVALID  --> |FAIL_UNEXPECTED| ERROR
        IDENTITY_CANDIDATE --> |FAIL_UNEXPECTED| ERROR
        UNIQUE_CANDIDATE --> |FAIL_UNEXPECTED| ERROR
        SIMILARITY_CANDIDATE --> |FAIL_UNEXPECTED| ERROR
        TO_CREATE --> |FAIL_UNEXPECTED| ERROR
        TO_UPDATE --> |FAIL_UNEXPECTED| ERROR 
        TO_LINK--> |FAIL_UNEXPECTED| ERROR
        TO_IGNORE --> |FAIL_UNEXPECTED| ERROR
    end
