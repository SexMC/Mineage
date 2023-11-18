-- #!mysql
-- #{ mineage

-- #    { create.aliases.table
CREATE TABLE IF NOT EXISTS Aliases
(Player VARCHAR(16) UNIQUE PRIMARY KEY,
    IP LONGTEXT,
    DID LONGTEXT,
    CID LONGTEXT);
-- #    }

-- #    { create.history.table
CREATE TABLE IF NOT EXISTS PunishmentHistory
(ID INT AUTO_INCREMENT PRIMARY KEY,
    Player VARCHAR(16),
    PDate INT,
    PType VARCHAR(255),
    Reason VARCHAR(255),
    Staff VARCHAR(16));
-- #    }

-- #    { create.bans.table
CREATE TABLE IF NOT EXISTS Bans
(Player VARCHAR(16) UNIQUE PRIMARY KEY,
    Reason VARCHAR(255),
    Staff VARCHAR(16),
    Happened INT,
    Expires INT);
-- #    }

-- #    { create.mutes.table
CREATE TABLE IF NOT EXISTS Mutes
(Player VARCHAR(16) UNIQUE PRIMARY KEY,
    Reason VARCHAR(255),
    Staff VARCHAR(16),
    Happened INT,
    Expires INT);
-- #    }

-- #    { register.aliases.entry
-- #    :player string
-- #    :ip string
-- #    :did string
-- #    :cid string
INSERT IGNORE INTO Aliases
    (Player, IP, DID, CID)
VALUES
    (:player, :ip, :did, :cid)
-- #    }

-- #    { update.aliases.entry
-- #    :player string
-- #    :ip string
-- #    :did string
-- #    :cid string
UPDATE Aliases SET
                   IP=:ip,
                   DID=:did,
                   CID=:cid
WHERE
        Player=:player;
-- #    }

-- #    { get.aliases.entry
-- #    :player string
SELECT * FROM Aliases WHERE Player=:player;
-- #    }

-- #    { get.aliases
SELECT * FROM Aliases;
-- #    }

-- #    { register.history.entry
-- #    :player string
-- #    :pdate int
-- #    :ptype string
-- #    :reason string
-- #    :staff string
INSERT IGNORE INTO PunishmentHistory
    (Player, PDate, PType, Reason, Staff)
VALUES
    (:player, :pdate, :ptype, :reason, :staff)
-- #    }

-- #    { get.history.entry
-- #    :player string
SELECT * FROM PunishmentHistory WHERE Player=:player;
-- #    }

-- #    { register.bans.entry
-- #    :player string
-- #    :reason string
-- #    :staff string
-- #    :happened int
-- #    :expires int
INSERT IGNORE INTO Bans
    (Player, Reason, Staff, Happened, Expires)
VALUES
    (:player, :reason, :staff, :happened, :expires)
-- #    }

-- #    { update.bans.entry
-- #    :player string
-- #    :reason string
-- #    :staff string
-- #    :happened int
-- #    :expires int
UPDATE Bans SET
                Reason=:reason,
                Staff=:staff,
                Happened=:happened,
                Expires=:expires
WHERE
    Player=:player;
-- #    }

-- #    { get.bans.entry
-- #    :player string
SELECT * FROM Bans WHERE Player=:player;
-- #    }

-- #    { clear.bans.entry
-- #    :player string
DELETE FROM Bans WHERE Player=:player;
-- #    }

-- #    { get.bans
SELECT * FROM Bans;
-- #    }

-- #    { register.mutes.entry
-- #    :player string
-- #    :reason string
-- #    :staff string
-- #    :happened int
-- #    :expires int
INSERT IGNORE INTO Mutes
    (Player, Reason, Staff, Happened, Expires)
VALUES
    (:player, :reason, :staff, :happened, :expires)
-- #    }

-- #    { update.mutes.entry
-- #    :player string
-- #    :reason string
-- #    :staff string
-- #    :happened int
-- #    :expires int
UPDATE Mutes SET
                Reason=:reason,
                Staff=:staff,
                Happened=:happened,
                Expires=:expires
WHERE
        Player=:player;
-- #    }

-- #    { get.mutes.entry
-- #    :player string
SELECT * FROM Mutes WHERE Player=:player;
-- #    }

-- #    { clear.mutes.entry
-- #    :player string
DELETE FROM Mutes WHERE Player=:player;
-- #    }

-- #    { get.mutes
SELECT * FROM Mutes;
-- #    }

-- #}
