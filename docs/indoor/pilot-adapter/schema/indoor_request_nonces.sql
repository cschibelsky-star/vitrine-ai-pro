-- Apply only to the selected HML shared database after approval.
-- No application code automatically creates or drops this table.
CREATE TABLE indoor_request_nonces (
  service_id VARCHAR(100) NOT NULL,
  nonce_hash CHAR(64) NOT NULL,
  expires_at BIGINT NOT NULL,
  PRIMARY KEY (service_id, nonce_hash)
);
-- Cleanup job may DELETE rows WHERE expires_at < current Unix timestamp
-- only after they are outside the allowed signature timestamp window.
