import { useState } from "react";
import { api } from "../../api";
import {
  useData,
  useAction,
  ActionNotice,
  Notice,
  Panel,
  Field,
  Select,
} from "../../components/DataUI";

export default function Entry() {
  const lots = useData("/lots"),
    spots = useData("/spots"),
    action = useAction(spots.reload);
  const [form, setForm] = useState({
    lot_id: "",
    space_number: "",
    plate_number: "",
  });

  async function submit(e) {
    e.preventDefault();
    if (
      await action.run(
        () => api("/sessions/entry", { method: "POST", body: form }),
        "Entry recorded",
      )
    )
      setForm({ ...form, plate_number: "", space_number: "" });
  }

  return (
    <Panel title="Record entry">
      <Notice
        error={lots.error || spots.error}
        loading={lots.loading || spots.loading}
      />

      <ActionNotice {...action} />

      <p>
        A reservation must be within its booked time and match the vehicle and
        space. Drivers add vehicles in Profile.
      </p>

      <form onSubmit={submit}>
        <Field
          label="Vehicle plate"
          required
          maxLength={20}
          value={form.plate_number}
          onChange={(e) => setForm({ ...form, plate_number: e.target.value })}
        />

        <Select
          label="Lot"
          required
          value={form.lot_id}
          onChange={(e) =>
            setForm({ ...form, lot_id: e.target.value, space_number: "" })
          }
        >
          <option value="">Choose lot</option>
          {(lots.data || []).map((l) => (
            <option key={l.id} value={l.id}>
              {l.name}
            </option>
          ))}
        </Select>

        <Select
          label="Space"
          required
          value={form.space_number}
          onChange={(e) => setForm({ ...form, space_number: e.target.value })}
        >
          <option value="">Choose reserved space</option>
          {(spots.data || [])
            .filter(
              (s) =>
                String(s.parking_lot_id) === form.lot_id &&
                s.status === "Available",
            )
            .map((s) => (
              <option key={s.id} value={s.space_number}>
                {s.space_number}
              </option>
            ))}
        </Select>
        
        <button className="btn btn-success" disabled={action.busy}>
          Record entry
        </button>
      </form>
    </Panel>
  );
}
