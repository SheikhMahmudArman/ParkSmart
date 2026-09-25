import { useState } from "react";
import { api, money } from "../../api";
import {
  useData,
  useAction,
  ActionNotice,
  Notice,
  Panel,
} from "../../components/DataUI";
import SessionsTable from "../../components/SessionsTable";

export default function Exit() {
  const state = useData("/sessions/active"),
    action = useAction(state.reload);
  const [receipt, setReceipt] = useState(null);
  async function exit(id) {
    setReceipt(null);
    await action.run(async () => {
      setReceipt(await api("/sessions/" + id + "/exit", { method: "POST" }));
    }, "Exit recorded. Booked price remains payable; any overstay fine appears in the driver Payment page.");
  }
  
  return (
    <Panel title="Record exit">
      <Notice {...state} />
      <ActionNotice {...action} />
      {receipt && (
        <p role="status">
          Duration: {receipt.duration_minutes} minutes. Metered cost:{" "}
          {money(receipt.total_cost)} (informational; do not charge twice).
        </p>
      )}
      <SessionsTable
        rows={state.data || []}
        actions={(r) => (
          <button
            className="btn btn-primary btn-sm"
            disabled={action.busy}
            onClick={() => exit(r.id)}
          >
            Record exit now
          </button>
        )}
      />
    </Panel>
  );
}
