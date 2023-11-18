-- #!mysql
-- #{ mineage
-- #    { clear.bans.entry
-- #    :player string
DELETE FROM Bans WHERE Player=:player;
-- #    }
-- #    { get.aliases
SELECT * FROM Aliases;
-- #    }
-- #    { get.bans
SELECT * FROM Bans;
-- #    }
-- # }
