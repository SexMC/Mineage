-- #!mysql
-- #{ mineage

-- #    { create.game.servers.table
CREATE TABLE IF NOT EXISTS BunkersGameServers
    (Server VARCHAR(16) UNIQUE PRIMARY KEY,
    MapName VARCHAR(16),
    BlueTeam TEXT,
    RedTeam TEXT,
    YellowTeam TEXT,
    GreenTeam TEXT,
    Spectating TEXT,
    WinningTeam VARCHAR(20));
-- #    }

-- #    { register.game.server.entry
-- #    :server string
INSERT IGNORE INTO BunkersGameServers
    (Server)
VALUES
    (:server)
-- #    }

-- #    { update.game.server.entry
-- #    :server string
-- #    :map_name string
-- #    :blue_team string
-- #    :red_team string
-- #    :yellow_team string
-- #    :green_team string
-- #    :spectating string
-- #    :winning_team string
UPDATE BunkersGameServers SET
    MapName=:map_name,
    BlueTeam=:blue_team,
    RedTeam=:red_team,
    YellowTeam=:yellow_team,
    GreenTeam=:green_team,
    Spectating=:spectating,
    WinningTeam=:winning_team
WHERE
    Server=:server;
-- #    }

-- #    { update.game.server.entry.spectating
-- #    :server string
-- #    :spectating string
UPDATE BunkersGameServers SET
    Spectating=:spectating
WHERE
        Server=:server;
-- #    }

-- #    { update.game.server.entry.all.team
-- #    :server string
-- #    :blue_team string
-- #    :red_team string
-- #    :yellow_team string
-- #    :green_team string
UPDATE BunkersGameServers SET
    BlueTeam=:blue_team,
    RedTeam=:red_team,
    YellowTeam=:yellow_team,
    GreenTeam=:green_team
WHERE
        Server=:server;
-- #    }

-- #    { update.game.server.entry.blue.team
-- #    :server string
-- #    :blue_team string
UPDATE BunkersGameServers SET
    BlueTeam=:blue_team
WHERE
        Server=:server;
-- #    }

-- #    { update.game.server.entry.red.team
-- #    :server string
-- #    :red_team string
UPDATE BunkersGameServers SET
    RedTeam=:red_team
WHERE
        Server=:server;
-- #    }

-- #    { update.game.server.entry.yellow.team
-- #    :server string
-- #    :yellow_team string
UPDATE BunkersGameServers SET
    YellowTeam=:yellow_team
WHERE
        Server=:server;
-- #    }

-- #    { update.game.server.entry.green.team
-- #    :server string
-- #    :green_team string
UPDATE BunkersGameServers SET
    GreenTeam=:green_team
WHERE
        Server=:server;
-- #    }

-- #    { get.game.server.entry
-- #    :server string
SELECT * FROM BunkersGameServers WHERE Server=:server;
-- #    }

-- #    { get.game.server.playing
-- #    :server string
SELECT BlueTeam, RedTeam, YellowTeam, GreenTeam FROM BunkersGameServers WHERE Server=:server;
-- #    }

-- #    { get.game.server.spectating
-- #    :server string
SELECT Spectating FROM BunkersGameServers WHERE Server=:server;
-- #    }

-- #    { get.game.server.winning.team
-- #    :server string
SELECT WinningTeam FROM BunkersGameServers WHERE Server=:server;
-- #    }

-- #}
