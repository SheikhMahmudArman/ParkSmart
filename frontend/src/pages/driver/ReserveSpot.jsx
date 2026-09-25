import { useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import { api, money } from "../../api";
import {
  useData,
  useAction,
  ActionNotice,
  Notice,
  Panel,
  Field,
  Select,
} from "../../components/DataUI";

export default function ReserveSpot() {
  const { id } = useParams(),
    { user } = useAuth(),
    navigate = useNavigate();
  const lot = useData("/lots/" + id),
    vehicles = useData(`/users/${user.id}/vehicles`);
  const [form, setForm] = useState({
    reservation_date: new Date().toLocaleDateString("en-CA", {
      timeZone: "Asia/Dhaka",
    }),
    start_time: "",
    end_time: "",
    vehicle_id: "",
  });

  const action = useAction();
  const change = (e) => setForm({ ...form, [e.target.name]: e.target.value });
  const minutes = (t) =>
    t ? Number(t.split(":")[0]) * 60 + Number(t.split(":")[1]) : 0;
  const hours = Math.ceil(
    (minutes(form.end_time) - minutes(form.start_time)) / 60,
  );

  async function submit(e) {
    e.preventDefault();
    if (
      await action.run(
        () =>
          api("/reservations", {
            method: "POST",
            body: { ...form, lot_id: Number(id) },
          }),
        "Reservation created",
      )
    )
      navigate("/driver/reservations");
  }
  
  return (
    <Panel title={`Reserve at ${lot.data?.name || "parking lot"}`}>
      <Notice
        error={lot.error || vehicles.error}
        loading={lot.loading || vehicles.loading}
      />
      <ActionNotice {...action} />
      {lot.data && vehicles.data && (
        <form onSubmit={submit}>
          <p>
            All dates and times are Asia/Dhaka. Start and end must be on the
            same date, with the start in the future.
          </p>
          <Field
            label="Date"
            name="reservation_date"
            type="date"
            value={form.reservation_date}
            onChange={change}
            required
          />
          <Field
            label="Start time"
            name="start_time"
            type="time"
            value={form.start_time}
            onChange={change}
            required
          />
          <Field
            label="End time"
            name="end_time"
            type="time"
            value={form.end_time}
            onChange={change}
            required
          />
          <Select
            label="Vehicle"
            name="vehicle_id"
            value={form.vehicle_id}
            onChange={change}
            required
          >
            <option value="">Choose vehicle</option>
            {vehicles.data.map((v) => (
              <option key={v.id} value={v.id}>
                {v.plate_number} · {v.make} {v.model}
              </option>
            ))}
          </Select>
          {!vehicles.data.length && (
            <p>
              Add a vehicle in <Link to="/driver/profile">Profile</Link> first.
            </p>
          )}
          <p>
            Estimated booked price:{" "}
            {hours > 0
              ? money(hours * Number(lot.data.hourly_rate))
              : "Choose a valid time interval"}
            . Each started hour is billed.
          </p>
          <button
            className="btn btn-success"
            disabled={action.busy || hours <= 0 || !form.vehicle_id}
          >
            Create reservation
          </button>
        </form>
      )}
    </Panel>
  );
}
