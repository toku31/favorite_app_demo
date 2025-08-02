CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT
);

INSERT INTO
    messages (content)
VALUES (
        'Hello from MySQL inside Docker!'
    );